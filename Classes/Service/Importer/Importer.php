<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer;

use Pixelant\PxaPmImporter\Adapter\AdapterInterface;
use Pixelant\PxaPmImporter\Context\ImportContext;
use Pixelant\PxaPmImporter\Domain\Model\DTO\PostponedProcessor;
use Pixelant\PxaPmImporter\Exception\MissingPropertyMappingException;
use Pixelant\PxaPmImporter\Exception\PostponeProcessorException;
use Pixelant\PxaPmImporter\Exception\ProcessorValidation\ErrorValidationException;
use Pixelant\PxaPmImporter\Logging\Logger;
use Pixelant\PxaPmImporter\Processors\FieldProcessorInterface;
use Pixelant\PxaPmImporter\Service\Source\SourceInterface;
use Pixelant\PxaPmImporter\Service\Status\ImportProgressStatus;
use Pixelant\PxaPmImporter\Traits\EmitSignalTrait;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class AbstractImporter
 * @package Pixelant\PxaPmImporter\Service\Importer
 */
class Importer implements ImporterInterface
{
    use EmitSignalTrait;

    /**
     * Localization statutes
     */
    const LOCALIZATION_FAILED = -1;
    const LOCALIZATION_SUCCESS = 1;
    const LOCALIZATION_DEFAULT_NOT_FOUND = 0;

    /**
     * @var AdapterInterface
     */
    protected $adapter = null;

    /**
     * @var ObjectManager
     */
    protected $objectManager = null;

    /**
     * @var PersistenceManager
     */
    protected $persistenceManager = null;

    /**
     * Identifier field name
     *
     * @var string
     */
    protected $identifier = 'id';

    /**
     * This flag allow/disallow create record with language uid > 0
     * in case parent record was not found
     *
     * @var bool
     */
    protected $allowCreateLocalizationIfDefaultNotFound = false;

    /**
     * Flag that allow to actually import new records,
     * if set to false - script will just update already imported values
     * Might be useful
     *
     * @var bool
     */
    protected $allowToCreateNewRecords = true;

    /**
     * Storage
     *
     * @var int
     */
    protected $pid = 0;

    /**
     * Update after reached batch size
     *
     * @var int
     */
    protected $batchSize = 50;

    /**
     * Update progress after reached batch size
     *
     * @var int
     */
    protected $batchProgressSize = 10;

    /**
     * Amount of items to be imported
     *
     * @var int
     */
    protected $amountOfImportItems = 0;

    /**
     * Keep track on already imported amount of items
     *
     * @var int
     */
    protected $batchProgressCount = 0;

    /**
     * Mapping rules
     *
     * @var array
     */
    protected $mapping = [];

    /**
     * Additonal settings
     *
     * @var array
     */
    protected $settings = [];

    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * Name of table where we import
     *
     * @var string
     */
    protected $dbTable = null;

    /**
     * Extbase model name
     *
     * @var string
     */
    protected $modelName = null;

    /**
     * @var RepositoryInterface
     */
    protected $repository = null;

    /**
     * @var SourceInterface
     */
    protected $source = null;

    /**
     * @var ImportProgressStatus
     */
    protected $importProgressStatus = null;

    /**
     * Array of import processor that should be run in postImport
     *
     * @var PostponedProcessor[]
     */
    protected $postponedProcessors = [];

    /**
     * Multiple array with default values for new record
     * Example:
     * [
     *    'values' => ['title' => ''],
     *    'types' => [Connection::PARAM_STR]
     * ]
     *
     * @var array
     */
    protected $defaultNewRecordFields = [];

    /**
     * @var ImportContext
     */
    protected $context = null;

    /**
     * Initialize
     *
     */
    public function __construct()
    {
        $this->logger = Logger::getInstance(__CLASS__);
    }

    /**
     * @param ObjectManager $objectManager
     */
    public function injectObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param PersistenceManager $manager
     */
    public function injectPersistenceManager(PersistenceManager $manager)
    {
        $this->persistenceManager = $manager;
    }

    /**
     * @param ImportContext $importContext
     */
    public function injectImportContext(ImportContext $importContext)
    {
        $this->context = $importContext;
    }

    /**
     * Run pre-import actions
     */
    public function preImport(): void
    {
    }

    /**
     * Start import
     *
     * @param SourceInterface $source
     * @param array $configuration
     * @throws \Exception
     */
    public function start(SourceInterface $source, array $configuration): void
    {
        try {
            $this->preImportPreparations($configuration);

            $this->runImport($source);
        } catch (\Exception $exception) {
            // If fail mark as done
            //$this->importProgressStatus->endImport($import);

            throw $exception;
        }
    }

    /**
     * Actions after import is finished
     */
    public function postImport(): void
    {
        //$this->importProgressStatus->endImport($import);
    }

    /**
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * Sets repository of import subject
     *
     * @param Repository $repository
     */
    public function setRepository(Repository $repository): void
    {
        $this->repository = $repository;
    }

    /**
     * Sets model name of import subject
     *
     * @param string $model
     */
    public function setModelName(string $model): void
    {
        $this->modelName = $model;
    }

    /**
     * Set table name of import subject
     *
     * @param string $table
     */
    public function setDatabaseTableName(string $table): void
    {
        $this->dbTable = $table;
    }

    /**
     * Sets default fields of new created record
     * Example:
     * [
     *    'values' => ['title' => ''],
     *    'types' => [\PDO::PARAM_STR]
     * ]
     *
     * @param array $fields
     */
    public function setDefaultNewRecordFields(array $fields): void
    {
        $defaultValues = $fields['values'] ?? [];
        $defaultTypes = $fields['types'] ?? [];
        if (count($defaultValues) !== count($defaultTypes)) {
            throw new \InvalidArgumentException(
                'Values in "defaultNewRecordFields" require corresponding types',
                1536138820478
            );
        }

        $this->defaultNewRecordFields = $fields;
    }

    /**
     * Setup stuff for import
     *
     * @param array $configuration
     */
    protected function preImportPreparations(array $configuration): void
    {
        $this->initializeAdapter($configuration);
        $this->determinateIdentifierField($configuration);
        $this->setMapping($configuration);
        $this->setSettings($configuration);
        $this->pid = (int)($configuration['pid'] ?? 0);

        if (isset($configuration['allowCreateLocalizationIfDefaultNotFound'])) {
            $this->allowCreateLocalizationIfDefaultNotFound =
                (bool)$configuration['allowCreateLocalizationIfDefaultNotFound'];
        }

        if (isset($configuration['allowToCreateNewRecords'])) {
            $this->allowToCreateNewRecords = (bool)$configuration['allowToCreateNewRecords'];
        }

        $this->checkStorage();
    }

    /**
     * Check if storage exist
     */
    protected function checkStorage(): void
    {
        if (BackendUtility::getRecord('pages', $this->pid, 'uid') === null) {
            throw new \RuntimeException('Storage with UID "' . $this->pid . '" doesn\'t exist', 1536310162347);
        }
    }

    /**
     * Set identifier field
     *
     * @param array $configuration
     */
    protected function determinateIdentifierField(array $configuration): void
    {
        $identifier = $configuration['identifierField'] ?? null;

        if ($identifier === null) {
            // @codingStandardsIgnoreStart
            throw new \UnexpectedValueException('Identifier could not be null, check your import settings', 1535983109427);
            // @codingStandardsIgnoreEnd
        }

        $this->identifier = $identifier;
    }

    /**
     * Initialize adapter
     *
     * @param array $configuration
     */
    protected function initializeAdapter(array $configuration): void
    {
        if (isset($configuration['adapter']) && !empty($configuration['adapter']['className'])) {
            $adapter = GeneralUtility::makeInstance($configuration['adapter']['className']);

            if (!($adapter instanceof AdapterInterface)) {
                // @codingStandardsIgnoreStart
                throw new \UnexpectedValueException('Adapter class "' . $configuration['adapter'] . '" must implement instance of AdapterInterface', 1535981100906);
                // @codingStandardsIgnoreEnd
            }

            $this->adapter = $adapter;

            $adapterConfiguration = $configuration['adapter'];
            unset($adapterConfiguration['className']);

            $this->adapter->initialize($adapterConfiguration);
        } else {
            throw new \RuntimeException('Could not resolve data adapter from import configuration', 1536047558452);
        }
    }

    /**
     * Set mapping rules
     *
     * @param array $configuration
     */
    protected function setMapping(array $configuration): void
    {
        if (empty($configuration['mapping']) || !is_array($configuration['mapping'])) {
            throw new \RuntimeException('No mapping found for importer "' . get_class($this) . '"', 1536054721032);
        }

        foreach ($configuration['mapping'] as $name => $fieldMapping) {
            $fieldConfiguration = $fieldMapping;
            unset($fieldConfiguration['processor'], $fieldConfiguration['property']);

            $this->mapping[$name] = [
                'property' => $fieldMapping['property'] ?? $name,
                'processor' => $fieldMapping['processor'] ?? false,
                'configuration' => $fieldConfiguration
            ];
        }
    }

    /**
     * Set settings from Yaml
     *
     * @param array $configuration
     */
    protected function setSettings(array $configuration): void
    {
        if (isset($configuration['settings']) && is_array($configuration['settings'])) {
            $this->settings = $configuration['settings'];
        }
    }

    /**
     * Get import id
     *
     * @param array $row
     * @return string
     */
    protected function getImportIdFromRow(array $row): string
    {
        $id = trim((string)($row[$this->identifier] ?? ''));

        if (empty($id)) {
            throw new \RuntimeException('Each row in import data should have import identifier', 1536058556481);
        }

        return $id;
    }

    /**
     * Import id hash
     *
     * @param string $id
     * @return string
     */
    protected function getImportIdHash(string $id): string
    {
        return MainUtility::getImportIdHash($id);
    }

    /**
     * Return DB row with record by import ID and language
     *
     * @param string $idHash
     * @param int $language
     * @return array|null
     */
    protected function getRecordByImportIdHash(string $idHash, int $language = 0): ?array
    {
        return MainUtility::getRecordByImportIdHash($idHash, $this->dbTable, $this->pid, $language);
    }

    /**
     * Get extbase model from raw record
     *
     * @param array $row
     * @return AbstractEntity
     */
    protected function mapRow(array $row): AbstractEntity
    {
        return MainUtility::convertRecordArrayToModel($row, $this->modelName);
    }

    /**
     * Add import data to model
     *
     * @param AbstractEntity $model
     * @param array $record
     * @param array $importRow
     * @return bool Return false if something went wrong, true otherwise
     */
    protected function populateModelWithImportData(
        AbstractEntity $model,
        array $record,
        array $importRow
    ): bool {
        foreach ($importRow as $field => $value) {
            try {
                $mapping = $this->getFieldMapping($field);
            } catch (MissingPropertyMappingException $exception) {
                // If missing mapping for identifier just skip it.
                // If mapping for identifier exist it'll process.
                if ($field === $this->identifier) {
                    continue;
                } else {
                    // If no mapping found and it's not identifier throw exception
                    throw $exception;
                }
            }

            $property = $mapping['property'];

            // If processor is set, it should set value for model property
            if (!empty($mapping['processor'])) {
                $processor = GeneralUtility::makeInstance($mapping['processor']);
                if (!($processor instanceof FieldProcessorInterface)) {
                    // @codingStandardsIgnoreStart
                    throw new \UnexpectedValueException('Processor "' . $mapping['processor'] . '" should be instance of "FieldProcessorInterface"', 1536128672117);
                    // @codingStandardsIgnoreEnd
                }

                $processor->init($model, $record, $property, $this, $mapping['configuration']);

                try {
                    $this->executeProcessor($processor, $value);
                } catch (PostponeProcessorException $exception) {
                    $this->postponeProcessor($processor, $value);
                } catch (ErrorValidationException $errorValidationException) {
                    $this->logger->error(sprintf(
                    // @codingStandardsIgnoreStart
                        'Failed validation for property "%s", with message - "%s", [ID - "%s", hash - "%s"]. Skipping record',
                        // @codingStandardsIgnoreEnd
                        $property,
                        $errorValidationException->getMessage(),
                        $processor->getProcessingDbRow()[self::DB_IMPORT_ID_FIELD],
                        $processor->getProcessingDbRow()[self::DB_IMPORT_ID_HASH_FIELD]
                    ));

                    return false;
                }
            } else {
                // Just set it if no processor
                $currentValue = ObjectAccess::getProperty($model, $property);
                if ($currentValue != $value) {
                    ObjectAccess::setProperty($model, $property, $value);
                }
            }
        }

        return true;
    }

    /**
     * Get mapping for single field
     *
     * @param string $field
     * @return array
     */
    protected function getFieldMapping(string $field): array
    {
        if (!isset($this->mapping[$field])) {
            // @codingStandardsIgnoreStart
            throw new MissingPropertyMappingException('Mapping configuration for field "' . $field . '" doesn\'t exist.', 1536062044810);
            // @codingStandardsIgnoreEnd
        }

        return $this->mapping[$field];
    }

    /**
     * Execute import field processor
     *
     * @param FieldProcessorInterface $processor
     * @param $value
     * @return void
     */
    protected function executeProcessor(FieldProcessorInterface $processor, $value): void
    {
        $processor->preProcess($value);
        if ($processor->isValid($value)) {
            $processor->process($value);
        } else {
            $this->logger->error(sprintf(
                'Processor error for row with ID "%s", with messages: %s',
                $processor->getProcessingDbRow()[self::DB_IMPORT_ID_FIELD],
                $processor->getValidationErrorsString()
            ));
        }
    }

    /**
     * Create new empty record
     *
     * @param string $id
     * @param string $idHash
     * @param int $language
     */
    protected function createNewEmptyRecord(string $id, string $idHash, int $language): void
    {
        $defaultValues = $this->defaultNewRecordFields['values'] ?? [];
        $defaultTypes = $this->defaultNewRecordFields['types'] ?? [];

        $values = array_merge(
            [
                self::DB_IMPORT_ID_FIELD => $id,
                self::DB_IMPORT_ID_HASH_FIELD => $idHash,
                'sys_language_uid' => $language,
                'pid' => $this->pid,
                'crdate' => time()
            ],
            $defaultValues
        );
        $types = array_merge(
            [
                Connection::PARAM_STR,
                Connection::PARAM_STR,
                Connection::PARAM_INT,
                Connection::PARAM_INT,
                Connection::PARAM_INT
            ],
            $defaultTypes
        );

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->dbTable)
            ->insert(
                $this->dbTable,
                $values,
                $types
            );
    }

    /**
     * Try to create localization if default record exist
     *
     * @param string $hash
     * @param int $language
     * @return int Localization status. 1 - success, 0 - no default record exist, but can continue, -1 - failed
     */
    protected function handleLocalization(string $hash, int $language): int
    {
        $defaultLanguageRecord = $this->getRecordByImportIdHash($hash, 0);
        if ($defaultLanguageRecord !== null) {
            $cmd = [];
            $cmd[$this->dbTable][(string)$defaultLanguageRecord['uid']]['localize'] = $language;

            $dataHandler = $this->getDataHandler();
            $dataHandler->start([], $cmd);
            $dataHandler->process_cmdmap();

            if (!empty($dataHandler->errorLog)) {
                foreach ($dataHandler->errorLog as $error) {
                    $this->logger->error($error);
                }

                return self::LOCALIZATION_FAILED;
            }
            $this->logger->info(sprintf(
                'Successfully localized record UID "%s" for language "%s"',
                $defaultLanguageRecord['uid'],
                $language
            ));
            // Assuming we are success
            return self::LOCALIZATION_SUCCESS;
        }

        $this->logger->info(sprintf(
            'Could not find default record for hash "%s" and language "%s"',
            $hash,
            $language
        ));

        return self::LOCALIZATION_DEFAULT_NOT_FOUND;
    }

    /**
     * Delete new record
     *
     * @param int $uid
     */
    protected function deleteNewRecord(int $uid): void
    {
        $this->logger->info('Delete new record with UID "' . $uid . '"');

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->dbTable)
            ->delete(
                $this->dbTable,
                ['uid' => $uid],
                [Connection::PARAM_INT]
            );
    }

    /**
     * Postpone processor for later execution
     *
     * @param FieldProcessorInterface $processor
     * @param mixed $value
     */
    protected function postponeProcessor(FieldProcessorInterface $processor, $value): void
    {
        // Increase amount of import items, since postponed processor means + 1 operation
        $this->amountOfImportItems++;

        $this->postponedProcessors[] = GeneralUtility::makeInstance(
            PostponedProcessor::class,
            $processor,
            $value
        );
    }

    /**
     * Execute postponed processors
     */
    protected function executePostponedProcessors(): void
    {
        $batchCount = 0;
        foreach ($this->postponedProcessors as $postponedProcessor) {
            $value = $postponedProcessor->getValue();
            $processor = $postponedProcessor->getProcessor();

            $entityUid = (int)$processor->getProcessingDbRow()['uid'];
            if ($entityUid === 0) {
                // @codingStandardsIgnoreStart
                throw new \UnexpectedValueException('Entity uid is not valid. Impossible to execute postponed processor', 1539935615795);// @codingStandardsIgnoreEnd
            }

            $record = BackendUtility::getRecord($this->dbTable, $entityUid);
            // Most likely record was deleted because of validation
            if ($record === null) {
                $this->logger->error(
                    'Failed executing postponed processor for record UID - ' . $entityUid . ', record not found.'
                );

                continue;
            }
            $model = $this->mapRow($record);

            // Re-init processor
            $processor->init(
                $model,
                $record,
                $processor->getProcessingProperty(),
                $this,
                $processor->getConfiguration()
            );

            try {
                $this->executeProcessor($processor, $value);
                // Update again if something changed
                if ($processor->getProcessingEntity()->_isDirty()) {
                    $this->repository->update($processor->getProcessingEntity());
                }
                if ((++$batchCount % $this->batchSize) === 0) {
                    $this->persistAndClear();
                }
            } catch (\Exception $exception) {
                if ($exception instanceof PostponeProcessorException
                    || $exception instanceof ErrorValidationException
                ) {
                    $this->logger->error(
                        'Failed executing postponed processor with message "' . $exception->getMessage() . '"'
                    );
                } else {
                    throw $exception;
                }
            }

            // If need to update progress status
            $this->updateImportProgress();
        }
        $this->postponedProcessors = [];
        $this->persistAndClear();
    }

    /**
     * Actual import
     *
     * @param SourceInterface $source
     */
    protected function runImport(SourceInterface $source): void
    {
        $languages = $this->adapter->getImportLanguages();
        $this->amountOfImportItems = $this->adapter->countAmountOfItems($source);
        $batchCount = -1;

        foreach ($languages as $language) {
            // Reset duplicated identifiers for each language
            $identifiers = [];
            // One row per record
            foreach ($source as $key => $rawRow) {
                // Persist and clear after every 50 iterations
                if ((++$batchCount % $this->batchSize) === 0) {
                    $this->persistAndClear();

                    $this->logger->info(sprintf(
                        'Memory usage after %d iterations - %s',
                        $batchCount,
                        MainUtility::getMemoryUsage()
                    ));
                }

                // Update progress on every iteration
                $this->updateImportProgress();

                if (!$this->adapter->includeRow($key, $rawRow)) {
                    // Skip
                    continue;
                }
                $row = $this->adapter->adaptRow($key, $rawRow, $language);
                $id = $this->getImportIdFromRow($row);
                $idHash = $this->getImportIdHash($id);

                // Log import processing
                $this->logger->info(sprintf(
                    'Start import for row ID - "%s", hash - "%s" and language - "%d"',
                    $id,
                    $idHash,
                    $language
                ));

                // Check if is unique for import
                if (in_array($idHash, $identifiers)) {
                    // @TODO maybe add some options what to do in this case?
                    $this->logger->error('Duplicated identifier found with value "' . $id . '"');
                } else {
                    $identifiers[] = $idHash;
                }

                $isNew = false;
                $record = $this->getRecordByImportIdHash($idHash, $language);

                // Try to create localization if doesn't exist
                if ($record === null && $language > 0) {
                    // Try to localize
                    switch ($this->handleLocalization($idHash, $language)) {
                        case self::LOCALIZATION_FAILED:
                            // Failed, skip record
                            $this->logger->error('Could not localize record with import id "' . $id . '"');
                            continue 2;
                        case self::LOCALIZATION_SUCCESS:
                            // If localization was created, fetch it.
                            $record = $this->getRecordByImportIdHash($idHash, $language);
                            break;
                        case self::LOCALIZATION_DEFAULT_NOT_FOUND:
                            if (false === $this->allowCreateLocalizationIfDefaultNotFound) {
                                // Skip if creation without default record is not allowed
                                continue 2;
                            }
                            break;
                    }
                }

                if ($record === null) {
                    if (!$this->allowToCreateNewRecords) {
                        $this->logger->info(sprintf(
                            'Creating of new records is forbidden. Skip row with UID "%s".',
                            $id
                        ));

                        // Skip it
                        continue;
                    }

                    // Create new record
                    $isNew = true;

                    $this->createNewEmptyRecord($id, $idHash, $language);

                    // Get new empty record
                    $record = $this->getRecordByImportIdHash($idHash, $language);
                    if ($record === null) {
                        // @codingStandardsIgnoreStart
                        throw new \RuntimeException('Error fetching new created record. This should never happen.', 1536063924811);
                        // @codingStandardsIgnoreEnd
                    }
                    $this->logger->info(sprintf(
                        'New record for table "%s" and language "%s", with UID "%s" was created.',
                        $this->dbTable,
                        $language,
                        $id
                    ));
                }

                $model = $this->mapRow($record);

                // If everything is fine try to populate model
                if (is_object($model)) {
                    try {
                        $result = $this->populateModelWithImportData($model, $record, $row);

                        if ($result === false) {
                            if ($isNew) {
                                // Clean new empty record
                                $this->deleteNewRecord((int)$record['uid']);
                            } else {
                                // Import might want to disable this record or do anything else
                                $this->emitSignal(__CLASS__, 'failedPopulatingImportModel', [$model]);
                            }
                            // Skip record where population failed
                            continue;
                        }
                    } catch (\Exception $exception) {
                        if ($isNew) {
                            // Clean new empty record
                            $this->deleteNewRecord((int)$record['uid']);
                        } else {
                            // Import might want to disable this record or do anything else
                            $this->emitSignal(__CLASS__, 'failedPopulatingImportModel', [$model]);
                        }

                        throw  $exception;
                    }
                } else {
                    $this->logger->error(sprintf(
                        'Failed mapping record with UID "%s" and import id "%s"',
                        $record['uid'],
                        $id
                    ));
                    if ($isNew) {
                        // Clean new empty record
                        $this->deleteNewRecord((int)$record['uid']);
                    }
                    // Go to next record
                    continue;
                }

                $this->emitSignal(__CLASS__, 'beforeUpdatingImportModel', [$model]);

                if ($model->_isDirty()) {
                    $this->logger->info(sprintf(
                        'Update record for table "%s", with UID "%s"',
                        $this->dbTable,
                        $model->getUid()
                    ));

                    $this->repository->update($model);

                    $this->emitSignal(__CLASS__, 'afterUpdatingImportModel', [$model]);
                }
            }

            $this->persistAndClear();
            // Execute postponed processors and persist again
            $this->executePostponedProcessors();
        }
    }

    /**
     * Persist all objects and clear persistance session
     */
    protected function persistAndClear(): void
    {
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
    }

    /**
     * Calculate current progress of import
     *
     * @return int
     */
    protected function getImportProgress(): float
    {
        if ($this->amountOfImportItems > 0) {
            return round($this->batchProgressCount / $this->amountOfImportItems * 100, 2);
        }

        return 100.00;
    }

    /**
     * Update progress
     */
    protected function updateImportProgress(): void
    {
        if ((++$this->batchProgressCount % $this->batchProgressSize) === 0) {
            $this->importProgressStatus->updateImportProgress(
                $this->import,
                $this->getImportProgress()
            );
        }
    }

    /**
     * @return DataHandler
     */
    protected function getDataHandler(): DataHandler
    {
        return GeneralUtility::makeInstance(DataHandler::class);
    }


}
