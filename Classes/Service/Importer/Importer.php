<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer;

use Pixelant\PxaPmImporter\Adapter\AdapterInterface;
use Pixelant\PxaPmImporter\Context\ImportContext;
use Pixelant\PxaPmImporter\Domain\Model\DTO\PostponedProcessor;
use Pixelant\PxaPmImporter\Domain\Repository\ProgressRepository;
use Pixelant\PxaPmImporter\Exception\Importer\FailedImportModelData;
use Pixelant\PxaPmImporter\Exception\Importer\LocalizationImpossibleException;
use Pixelant\PxaPmImporter\Exception\MissingPropertyMappingException;
use Pixelant\PxaPmImporter\Exception\PostponeProcessorException;
use Pixelant\PxaPmImporter\Exception\ProcessorValidation\ErrorValidationException;
use Pixelant\PxaPmImporter\Logging\Logger;
use Pixelant\PxaPmImporter\Processors\FieldProcessorInterface;
use Pixelant\PxaPmImporter\Service\Source\SourceInterface;
use Pixelant\PxaPmImporter\Traits\EmitSignalTrait;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\QueryGenerator;
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
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var RepositoryInterface
     */
    protected $repository = null;

    /**
     * @var SourceInterface
     */
    protected $source = null;

    /**
     * @var ProgressRepository
     */
    protected $progressRepository = null;

    /**
     * @var ImportContext
     */
    protected $context = null;

    /**
     * Array of import processor that should be run in postImport
     *
     * @var PostponedProcessor[]
     */
    protected $postponedProcessors = [];

    /**
     * Identifier field name
     *
     * @var string
     */
    protected $identifier = 'id';

    /**
     * By default all operations are allowed
     * Possible options 'create,update,localize,createLocalize'
     *
     * @var string
     */
    protected $allowedOperations = 'create,update,localize';

    /**
     * Persist changes and clear after reached batch size
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
     * Keep track on already imported amount of items
     *
     * @var int
     */
    protected $batchProgressCount = 0;

    /**
     * Amount of items to be imported
     *
     * @var int
     */
    protected $amountOfImportItems = 0;

    /**
     * Mapping rules
     *
     * @var array
     */
    protected $mapping = [];

    /**
     * Importer configuration
     *
     * @var array
     */
    protected $configuration = [];

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
     * Multiple array with default values for new record
     * Example:
     * [
     *   'title' => 'default title'
     * ]
     *
     *
     * @var array
     */
    protected $defaultNewRecordFields = [];

    /**
     * Array of identifier for one language import
     *
     * @var array
     */
    protected $identifiers = [];

    /**
     * Array of new created records uids
     *
     * @var array
     */
    protected $newUids = [];

    /**
     * Array of updated records uids
     *
     * @var array
     */
    protected $updatedUids = [];

    /**
     * Keep progress record UID
     *
     * @var int
     */
    protected $progressUid = 0;

    /**
     * Initialize
     *
     * @param ProgressRepository $progressRepository
     */
    public function __construct(ProgressRepository $progressRepository)
    {
        $this->progressRepository = $progressRepository;
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
     * Initialize importer
     *
     * @param SourceInterface $source
     * @param array $configuration
     * @return ImporterInterface
     */
    public function initialize(SourceInterface $source, array $configuration): ImporterInterface
    {
        $this->setConfiguration($configuration);
        $this->setSource($source);

        $this->initializeAdapter($configuration);
        $this->countSourceWithAdapter();

        $this->determinateIdentifierField($configuration);
        $this->determinateAllowedOperations($configuration);
        $this->determinateDefaultNewRecordFields($configuration);

        $this->setMappingRules($configuration);

        $this->initializeExtbaseRequired($configuration);

        $this->initializeContextStorage($configuration);
        $this->initializeContextNewRecordsPid($configuration);

        $this->initProgress();

        return $this;
    }

    /**
     * Execute import
     */
    public function execute(): void
    {
        try {
            $this->runImport();
            $this->deleteProgress();
        } catch (\Exception $exception) {
            // If fail mark as done
            $this->deleteProgress();

            throw $exception;
        }
    }

    /**
     * Initialize storage
     *
     * @param array $configuration
     */
    protected function initializeContextStorage(array $configuration): void
    {
        $pids = GeneralUtility::intExplode(',', $configuration['storage']['pid'] ?? '');
        $recursive = intval($configuration['storage']['recursive'] ?? 0);

        if (empty($pids)) {
            throw new \UnexpectedValueException('Importer storage could not be empty', 1571379428146);
        }

        if ($recursive > 0) {
            $queryGenerator = GeneralUtility::makeInstance(QueryGenerator::class);
            foreach ($pids as $pid) {
                $pidList = $queryGenerator->getTreeList($pid, $recursive, 0, 1);
                $pids = array_merge($pids, GeneralUtility::intExplode(',', $pidList));
            }
        }

        // Save in context too
        $this->context->setStoragePids(array_unique($pids));
    }

    /**
     * Get pid for new records from configuration
     *
     * @param array $configuration
     */
    protected function initializeContextNewRecordsPid(array $configuration): void
    {
        $pid = intval($configuration['importNewRecords']['pid'] ?? 0);

        if ($this->isAllowedOperation('create')) {
            if ($pid <= 0) {
                throw new \UnexpectedValueException('New records pid could not be empty', 1571381562830);
            }

            if (!in_array($pid, $this->context->getStoragePids())) {
                throw new \UnexpectedValueException('New records pid need to one of the storage pids.', 1571391396860);
            }
        }

        $this->context->setNewRecordsPid($pid);
    }

    /**
     * Set identifier field
     *
     * @param array $configuration
     */
    protected function determinateIdentifierField(array $configuration): void
    {
        if (!empty($configuration['identifierField'])) {
            $this->identifier = $configuration['identifierField'];
        }
    }

    /**
     * Override allowed operations
     *
     * @param array $configuration
     */
    protected function determinateAllowedOperations(array $configuration): void
    {
        if (!empty($configuration['allowedOperations'])) {
            $this->allowedOperations = $configuration['allowedOperations'];
        }
    }

    /**
     * Set default fields for new record
     *
     * @param array $configuration
     */
    protected function determinateDefaultNewRecordFields(array $configuration): void
    {
        if (!empty($configuration['importNewRecords']['defaultFields'])) {
            if (!is_array($configuration['importNewRecords']['defaultFields'])) {
                throw new \InvalidArgumentException('"defaultFields" expect to be array', 1571382096248);
            }

            $this->defaultNewRecordFields = $configuration['importNewRecords']['defaultFields'];
        }
    }

    /**
     * Initialize adapter
     *
     * @param array $configuration
     */
    protected function initializeAdapter(array $configuration): void
    {
        if (!empty($configuration['adapter']['className'])) {
            $adapter = $this->objectManager->get($configuration['adapter']['className']);

            if (!$adapter instanceof AdapterInterface) {
                // @codingStandardsIgnoreStart
                throw new \UnexpectedValueException('Adapter class "' . $configuration['adapter'] . '" must implement AdapterInterface', 1535981100906);
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
     * Count source items
     */
    protected function countSourceWithAdapter(): void
    {
        $this->amountOfImportItems = $this->adapter->countAmountOfItems($this->source);
    }

    /**
     * Set mapping rules
     *
     * @param array $configuration
     */
    protected function setMappingRules(array $configuration): void
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
     * Set configuration from Yaml
     *
     * @param array $configuration
     */
    protected function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    /**
     * Set import source
     *
     * @param SourceInterface $source
     */
    protected function setSource(SourceInterface $source): void
    {
        $this->source = $source;
    }

    /**
     * Init model name, repository and DB table name
     *
     * @param array $configuration
     */
    protected function initializeExtbaseRequired(array $configuration): void
    {
        // Check model name
        if (empty($configuration['domainModel'])) {
            throw new \UnexpectedValueException('"domainModel" could not be empty', 1571382616270);
        }
        $domainModel = $configuration['domainModel'];

        if (!class_exists($domainModel) || !is_subclass_of($domainModel, AbstractEntity::class)) {
            // @codingStandardsIgnoreStart
            throw new \RuntimeException("Domain model '$domainModel' is invalid. It should exist and extend AbstractEntity", 1571382750550);
            // @codingStandardsIgnoreEnd
        }

        $this->modelName = $domainModel;

        // Get repository using model name
        $repository = str_replace('\\Model\\', '\\Repository\\', $domainModel) . 'Repository';
        if (!class_exists($repository)) {
            throw new \RuntimeException("Repository '$repository' doesn't exist", 1571382879964);
        }

        $this->repository = $this->objectManager->get($repository);

        // Set table of domain model
        $this->dbTable = MainUtility::getTableNameByModelName($domainModel);
    }

    /**
     * Init progress status records
     */
    protected function initProgress(): void
    {
        $configuration = $this->context->getImportConfigurationSource();
        $progress = $this->progressRepository->findByConfiguration($configuration);

        if ($progress !== null) {
            $this->progressUid = $progress['uid'];
        } else {
            $this->progressUid = $this->progressRepository->addWithConfiguration($configuration);
        }
    }

    /**
     * End of import, remove progress record
     */
    protected function deleteProgress(): void
    {
        $this->progressRepository->deleteProgress($this->progressUid);
    }

    /**
     * Check if given operation is allowed
     *
     * @param string $operation
     * @return bool
     */
    protected function isAllowedOperation(string $operation): bool
    {
        return GeneralUtility::inList($this->allowedOperations, $operation);
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
        return MainUtility::getRecordByImportIdHash(
            $idHash,
            $this->dbTable,
            $this->context->getStoragePids(),
            $language
        );
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
     * @return void
     */
    protected function populateModelWithImportData(
        $model,
        array $record,
        array $importRow
    ): void {
        if (!is_object($model)) {
            $type = gettype($model);
            throw new FailedImportModelData(
                "Expect model to be an object, '$type' given.",
                1571387997402
            );
        }

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
                $processor = $this->createProcessor($mapping);
                $processor->init($model, $record, $property, $mapping['configuration']);

                try {
                    $this->executeProcessor($processor, $value);
                } catch (PostponeProcessorException $exception) {
                    $this->postponeProcessor($processor, $value);
                } catch (ErrorValidationException $errorValidationException) {
                    $this->logProcessorValidationError($processor, $errorValidationException);

                    throw new FailedImportModelData('Processor validation error', 1571387529039);
                }
            } else {
                // Just set it if no processor
                $currentValue = ObjectAccess::getProperty($model, $property);
                if ($currentValue != $value) {
                    ObjectAccess::setProperty($model, $property, $value);
                }
            }
        }
    }

    /**
     * Log error about validation error
     *
     * @param FieldProcessorInterface $processor
     * @param ErrorValidationException $exception
     */
    protected function logProcessorValidationError(
        FieldProcessorInterface $processor,
        ErrorValidationException $exception
    ): void {
        $this->logger->error(sprintf(
            'Error mapping property. Skipping record. [ID-"%s", UID-"%s", PROP-"%s", REASON-"%s"].',
            $processor->getProcessingDbRow()[self::DB_IMPORT_ID_FIELD],
            $processor->getProcessingDbRow()['uid'],
            $processor->getProcessingProperty(),
            $exception->getMessage()
        ));
    }

    /**
     * Create processor
     *
     * @param array $mapping
     * @return FieldProcessorInterface
     */
    protected function createProcessor(array $mapping): FieldProcessorInterface
    {
        return $this->objectManager->get($mapping['processor']);
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
        }
    }

    /**
     * Try to import(create) new record
     *
     * @param string $id
     * @param string $hash
     * @param int $language
     * @return array|null
     */
    protected function tryCreateNewRecord(string $id, string $hash, int $language): ?array
    {
        if (!$this->isAllowedOperation('create')) {
            $this->logger->info(sprintf(
                'Could not create record [ID-"%s", REASON-"Not allowed by operations"].',
                $id
            ));

            return null;
        }

        $this->createNewEmptyRecord($id, $hash, $language);

        // Get new empty record
        $record = $this->getRecordByImportIdHash($hash, $language);

        if ($record === null) {
            // @codingStandardsIgnoreStart
            throw new \RuntimeException('Error fetching new created record. This should never happen.', 1536063924811);
            // @codingStandardsIgnoreEnd
        }

        $this->logger->info(sprintf(
            'New record created [ID-"%s", LANG-"%d", TABLE-"%s", ].',
            $id,
            $language,
            $this->dbTable
        ));

        return $record;
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
        $time = time();
        $values = array_merge(
            [
                self::DB_IMPORT_ID_FIELD => $id,
                self::DB_IMPORT_ID_HASH_FIELD => $idHash,
                'sys_language_uid' => $language,
                'pid' => $this->context->getNewRecordsPid(),
                'crdate' => $time,
                'tstamp' => $time,
            ],
            $this->defaultNewRecordFields
        );

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->dbTable)
            ->insert(
                $this->dbTable,
                $values
            );
    }

    /**
     * Try to localize record
     *
     * @param string $hash
     * @param int $language
     * @return array|null
     */
    protected function tryLocalizeRecord(string $hash, int $language): ?array
    {
        if (!$this->isAllowedOperation('localize')) {
            throw new LocalizationImpossibleException('Localization not allowed', 1571385017406);
        }

        switch ($this->handleLocalization($hash, $language)) {
            case self::LOCALIZATION_FAILED:
                throw new LocalizationImpossibleException('Localization went with errors', 1571384846222);
            case self::LOCALIZATION_SUCCESS:
                // If localization was created, fetch it.
                $record = $this->getRecordByImportIdHash($hash, $language);

                $this->logger->info(sprintf(
                    'Localized record [UID-"%s", ID-"%s", LANG-"%s"]',
                    $record['uid'],
                    $record[self::DB_IMPORT_ID_FIELD],
                    $language
                ));

                return $record;
                break;
            case self::LOCALIZATION_DEFAULT_NOT_FOUND:
                if (!$this->isAllowedOperation('createLocalize')) {
                    throw new LocalizationImpossibleException('Default language record not found', 1571385047840);
                }

                // Not localized
                return null;
        }
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

            // Assuming we are success
            return self::LOCALIZATION_SUCCESS;
        }


        return self::LOCALIZATION_DEFAULT_NOT_FOUND;
    }

    /**
     * Delete new record
     *
     * @param int $uid
     */
    protected function deleteNewRecord(int $uid): void
    {
        $this->logger->info(sprintf('Delete record[UID-"%d"]', $uid));

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
            } catch (PostponeProcessorException | ErrorValidationException $exception) {
                $this->logger->error(
                    "Postponed processor failed [REASON-'{$exception->getMessage()}'] "
                );
            }

            // If need to update progress status
            $this->updateImportProgress();
        }

        $this->postponedProcessors = [];
        $this->persistAndClear();
    }

    /**
     * Actual import
     */
    protected function runImport(): void
    {
        $languages = $this->adapter->getImportLanguages();

        foreach ($languages as $language) {
            // Reset duplicated identifiers for each language
            $this->identifiers = [];
            // One row per record
            foreach ($this->source as $key => $rawRow) {
                // Update progress on every iteration
                $this->updateImportProgress();

                // Persist if reach limit
                $this->batchPersist();

                if (!$this->adapter->includeRow($key, $rawRow)) {
                    // Skip
                    continue;
                }

                $row = $this->adapter->adaptRow($key, $rawRow, $language);
                $id = $this->getImportIdFromRow($row);
                $idHash = $this->getImportIdHash($id);

                // Log import processing
                $this->logger->info(sprintf(
                    'Start import for row [ID-"%s", LANG-"%d"]',
                    $id,
                    $language
                ));

                // Check if is unique for import
                $this->checkIfIdentifierUnique($idHash, $id);

                $isNew = false;
                $record = $this->getRecordByImportIdHash($idHash, $language);

                // Try to create localization if doesn't exist
                if ($record === null && $language > 0) {
                    // Try to localize
                    try {
                        $record = $this->tryLocalizeRecord($idHash, $language);
                    } catch (LocalizationImpossibleException $exception) {
                        $this->logger->error("Localization failed [Message-'{$exception->getMessage()}']");
                        continue;
                    }
                }

                if ($record === null) {
                    $record = $this->tryCreateNewRecord($id, $idHash, $language);
                    $isNew = $record !== null;

                    // Not allowed to create, go to next
                    if (!$isNew) {
                        continue;
                    }
                }

                $model = $this->mapRow($record);

                try {
                    $this->populateModelWithImportData($model, $record, $row);
                } catch (\Exception $exception) {
                    $this->logger->error(sprintf(
                        'Failed import model [ID-"%s", UID-"%s", REASON-"%s"].',
                        $id,
                        $record['uid'],
                        $exception->getMessage()
                    ));

                    if ($isNew) {
                        $this->deleteNewRecord($record['uid']);
                    }

                    $this->emitSignal(__CLASS__, 'failedPopulatingImportModel', [$model, $isNew]);

                    // On failed mapping just skip it
                    if ($exception instanceof FailedImportModelData) {
                        continue;
                    } else {
                        // On any other exception throw it
                        throw $exception;
                    }
                }

                $this->persistSingleEntity($model, $id, $record, $isNew);
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
            $this->progressRepository->updateProgress(
                $this->progressUid,
                $this->getImportProgress()
            );
        }
    }

    /**
     * Persist items in queue
     */
    protected function batchPersist(): void
    {
        // Persist and clear after reached batch size
        if (($this->batchProgressCount % $this->batchSize) === 0) {
            $this->persistAndClear();

            $this->logger->info(sprintf(
                'Memory usage after %d iterations - %s',
                $this->batchProgressCount,
                MainUtility::getMemoryUsage()
            ));
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
     * Save changes for single entity
     *
     * @param AbstractEntity $model
     * @param string $id
     * @param array $record
     * @param bool $isNew
     */
    protected function persistSingleEntity(AbstractEntity $model, string $id, array $record, bool $isNew): void
    {
        $this->emitSignal(__CLASS__, 'beforePersistImportModel', [$model]);

        if ($model->_isDirty()) {
            $this->logger->info(sprintf(
                'Update record [ID-"%s", UID-"%s", TABLE-"%s", ]',
                $id,
                $record['uid'],
                $this->dbTable
            ));

            $this->repository->update($model);

            if ($isNew) {
                $this->newUids[] = $record['uid'];
            } else {
                $this->updatedUids[] = $record['uid'];
            }

            $this->emitSignal(__CLASS__, 'afterPersistImportModel', [$model]);
        }
    }

    /**
     * Check if ID was already in import
     *
     * @param string $idHash
     * @param string $id
     */
    protected function checkIfIdentifierUnique(string $idHash, string $id): void
    {
        if (in_array($idHash, $this->identifiers)) {
            // @TODO does this mean that it records need to be update again?
            // Persist, we need to save changes done before
            $this->persistenceManager->persistAll();

            $this->logger->notice("Duplicated identifier[ID-'$id']");
        } else {
            $this->identifiers[] = $idHash;
        }
    }
}
