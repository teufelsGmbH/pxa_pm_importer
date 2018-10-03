<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer;

use Pixelant\PxaPmImporter\Adapter\AdapterInterface;
use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Exception\MissingPropertyMappingException;
use Pixelant\PxaPmImporter\Exception\PostponeProcessorException;
use Pixelant\PxaPmImporter\Logging\Logger;
use Pixelant\PxaPmImporter\Processors\FieldProcessorInterface;
use Pixelant\PxaPmImporter\Service\Source\SourceInterface;
use Pixelant\PxaPmImporter\Traits\EmitSignalTrait;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class AbstractImporter
 * @package Pixelant\PxaPmImporter\Service\Importer
 */
abstract class AbstractImporter implements ImporterInterface
{
    use EmitSignalTrait;

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
     * Storage
     *
     * @var int
     */
    protected $pid = 0;

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
     * @var Import
     */
    protected $import = null;

    /**
     * Array of import processor that should be run in postImport
     *
     * @var FieldProcessorInterface[]
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
     * Initialize
     *
     * @param Logger $logger
     */
    public function __construct(Logger $logger = null)
    {
        $this->logger = $logger !== null ? $logger : Logger::getInstance(__CLASS__);
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->persistenceManager = $this->objectManager->get(PersistenceManager::class);
    }

    /**
     * Start import
     *
     * @param SourceInterface $source
     * @param Import $import
     * @param array $configuration
     */
    public function start(SourceInterface $source, Import $import, array $configuration = []): void
    {
        $this->import = $import;
        $this->preImportPreparations($source, $configuration);
        $this->initImporterRelated();

        $this->runImport();
    }

    /**
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * Setup stuff for import
     *
     * @param SourceInterface $source
     * @param array $configuration
     */
    protected function preImportPreparations(SourceInterface $source, array $configuration = []): void
    {
        $this->initializeAdapter($source, $configuration);
        $this->determinateIdentifierField($configuration);
        $this->setMapping($configuration);
        $this->setSettings($configuration);
        $this->pid = (int)($configuration['pid'] ?? 0);

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

        $this->emitSignal('determinateIdentifierField', [&$identifier]);

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
     * @param SourceInterface $source
     * @param array $configuration
     */
    protected function initializeAdapter(SourceInterface $source, array $configuration): void
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

            $this->adapter->adapt($source->getSourceData(), $adapterConfiguration);
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
        $id = strtolower(trim((string)($row[$this->identifier] ?? '')));

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
     * Init all stuff that is specified for each import
     */
    protected function initImporterRelated(): void
    {
        $this->initDbTableName();
        $this->initModelName();
        $this->initRepository();
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->dbTable);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $row = $queryBuilder
            ->select('*')
            ->from($this->dbTable)
            ->where(
                $queryBuilder->expr()->eq(
                    self::DB_IMPORT_ID_HASH_FIELD,
                    $queryBuilder->createNamedParameter($idHash, Connection::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($language, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($this->pid, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        return is_array($row) ? $row : null;
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
                    $processingResult = $this->executeProcessor($processor, $value);
                    if (false === $processingResult) {
                        return false;
                    }
                } catch (PostponeProcessorException $exception) {
                    $this->postponeProcessor($processor, $value);
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
     * @return bool
     */
    protected function executeProcessor(FieldProcessorInterface $processor, $value): bool
    {
        $processor->preProcess($value);
        if ($processor->isValid($value)) {
            $processor->process($value);

            return true;
        } else {
            $this->logger->error(sprintf(
                'Property "%s" validation failed for import ID "%s(hash:%s)", with messages: %s',
                $processor->getProcessingProperty(),
                $processor->getProcessingDbRow()[self::DB_IMPORT_ID_FIELD],
                $processor->getProcessingDbRow()[self::DB_IMPORT_ID_HASH_FIELD],
                $processor->getValidationErrorsString()
            ));

            return false;
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
        if (count($defaultValues) !== count($defaultTypes)) {
            // @codingStandardsIgnoreStart
            throw new \UnexpectedValueException('Values in "defaultNewRecordFields" require corresponding types', 1536138820478);
            // @codingStandardsIgnoreEnd
        }

        $values = array_merge(
            [
                self::DB_IMPORT_ID_FIELD => $id,
                self::DB_IMPORT_ID_HASH_FIELD => $idHash,
                'sys_language_uid' => $language,
                'pid' => $this->pid
            ],
            $defaultValues
        );
        $types = array_merge(
            [
                Connection::PARAM_STR,
                Connection::PARAM_STR,
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

                return -1;
            }
            $this->logger->info(sprintf(
                'Successfully localized record UID "%s" for language "%s"',
                $defaultLanguageRecord['uid'],
                $language
            ));
            // Assuming we are success
            return 1;
        }

        $this->logger->info(sprintf(
            'Could not find default record for hash "%s" and language "%s"',
            $hash,
            $language
        ));

        return 0;
    }

    /**
     * Delete new record
     *
     * @param int $uid
     */
    protected function deleteNewRecord(int $uid)
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
        $this->postponedProcessors[] = [
            'value' => $value,
            'processorInstance' => $processor
        ];
    }

    /**
     * Execute postponed processors
     */
    protected function executePostponedProcessors(): void
    {
        foreach ($this->postponedProcessors as $postponedProcessor) {
            $value = $postponedProcessor['value'];
            /** @var FieldProcessorInterface $processor */
            $processor = $postponedProcessor['processorInstance'];

            try {
                $this->executeProcessor($processor, $value);
                // Update again if something changed
                if ($processor->getProcessingEntity()->_isDirty()) {
                    $this->repository->update($processor->getProcessingEntity());
                }
            } catch (PostponeProcessorException $exception) {
                $this->logger->error(
                    'Failed executing postponed processor with message "' . $exception->getMessage() . '"'
                );
            }
        }
        unset($this->postponedProcessors);
        $this->postponedProcessors = [];
    }

    /**
     * Check for duplicated identifiers in data
     *
     * @param array $data
     */
    protected function checkForDuplicatedIdentifiers(array $data): void
    {
        $identifiers = [];
        foreach ($data as $row) {
            $id = $this->getImportIdFromRow($row);
            if (in_array($id, $identifiers)) {
                throw new \RuntimeException('Duplicated identifier "' . $id . '" found.', 1536316466353);
            }
            $identifiers[] = $id;
        }
    }

    /**
     * Actual import
     */
    protected function runImport(): void
    {
        $languages = $this->adapter->getLanguages();

        foreach ($languages as $language) {
            $data = $this->adapter->getLanguageData($language);
            $this->checkForDuplicatedIdentifiers($data);

            // One row per record
            foreach ($data as $row) {
                $id = $this->getImportIdFromRow($row);
                $idHash = $this->getImportIdHash($id);
                $isNew = false;
                $record = $this->getRecordByImportIdHash($idHash, $language);

                // Try to create localization if doesn't exist
                if ($record === null && $language > 0) {
                    // Try to localize
                    $localizeStatus = $this->handleLocalization($idHash, $language);
                    // Failed, skip record
                    if ($localizeStatus === -1) {
                        $this->logger->error('Could not localize record with import id "' . $id . '"');
                        continue;
                    }
                    // If localization was created, fetch it,
                    // otherwise it'll create independent record
                    if ($localizeStatus === 1) {
                        $record = $this->getRecordByImportIdHash($idHash, $language);
                    }
                }

                if ($record === null) {
                    $isNew = true;
                    $this->logger->info(sprintf(
                        'New record for table "%s" and language "%s", with UID "%s" was created.',
                        $this->dbTable,
                        $language,
                        $id
                    ));

                    $this->createNewEmptyRecord($id, $idHash, $language);

                    // Get new empty record
                    $record = $this->getRecordByImportIdHash($idHash, $language);
                    if ($record === null) {
                        // @codingStandardsIgnoreStart
                        throw new \RuntimeException('Error fetching new created record. This should never happen.', 1536063924811);
                        // @codingStandardsIgnoreEnd
                    }
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
                            }
                            // Skip record where population failed
                            continue;
                        }
                    } catch (\Exception $exception) {
                        if ($isNew) {
                            // Clean new empty record
                            $this->deleteNewRecord((int)$record['uid']);
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

                $this->emitSignal('beforeUpdatingImportModel', [$model]);

                if ($model->_isDirty()) {
                    $this->logger->info(sprintf(
                        'Update record for table "%s", with UID "%s"',
                        $this->dbTable,
                        $model->getUid()
                    ));

                    $this->repository->update($model);
                }
            }

            // Persist after each language import. Next language might require data for localization.
            $this->persistenceManager->persistAll();

            // Execute postponed processors and persist again
            $this->executePostponedProcessors();
            $this->persistenceManager->persistAll();
        }
    }

    /**
     * @return DataHandler
     */
    protected function getDataHandler(): DataHandler
    {
        return GeneralUtility::makeInstance(DataHandler::class);
    }

    /**
     * Set import target dbTable
     */
    abstract protected function initDbTableName(): void;

    /**
     * Set import target extbase model name
     */
    abstract protected function initModelName(): void;

    /**
     * Init target model repository
     */
    abstract protected function initRepository(): void;
}
