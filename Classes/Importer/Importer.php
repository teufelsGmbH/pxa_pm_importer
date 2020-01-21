<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Importer;

use Pixelant\PxaPmImporter\Adapter\AdapterInterface;
use Pixelant\PxaPmImporter\Context\ImportContext;
use Pixelant\PxaPmImporter\Domain\Repository\ImportRecordRepository;
use Pixelant\PxaPmImporter\Domain\Repository\ProgressRepository;
use Pixelant\PxaPmImporter\Exception\Importer\LocalizationImpossibleException;
use Pixelant\PxaPmImporter\Exception\MissingImportField;
use Pixelant\PxaPmImporter\Logging\Logger;
use Pixelant\PxaPmImporter\Processors\FieldProcessorInterface;
use Pixelant\PxaPmImporter\Processors\PreProcessorInterface;
use Pixelant\PxaPmImporter\Service\Cache\CacheService;
use Pixelant\PxaPmImporter\Source\SourceInterface;
use Pixelant\PxaPmImporter\Traits\EmitSignalTrait;
use Pixelant\PxaPmImporter\Utility\ExtbaseUtility;
use Pixelant\PxaPmImporter\Utility\HashUtility;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use Pixelant\PxaPmImporter\Validation\ValidationManager;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\ClassNamingUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class AbstractImporter
 * @package Pixelant\PxaPmImporter\Importer
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
     * @var ImportRecordRepository
     */
    protected $importRecordRepository = null;

    /**
     * @var ValidationManager
     */
    protected $validator = null;

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
     * @param ImportRecordRepository $importRecordRepository
     */
    public function __construct(ProgressRepository $progressRepository, ImportRecordRepository $importRecordRepository)
    {
        $this->importRecordRepository = $importRecordRepository;
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
     * Execute import
     *
     * @param SourceInterface $source
     * @param array $configuration
     * @throws \Exception
     */
    public function execute(SourceInterface $source, array $configuration): void
    {
        $this->initialize($source, $configuration);

        try {
            // Run import
            $this->runImport();

            // Clear cache
            $this->clearCache();

            // Clean progress
            $this->deleteProgress();
        } catch (\Exception $exception) {
            // If fail mark as done
            $this->deleteProgress();

            throw $exception;
        }
    }

    /**
     * Initialize importer
     *
     * @param SourceInterface $source
     * @param array $configuration
     */
    public function initialize(SourceInterface $source, array $configuration): void
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

        $this->initializeValidator($configuration);
    }

    /**
     * Initialize storage
     *
     * @param array $configuration
     */
    protected function initializeContextStorage(array $configuration): void
    {
        if (empty($configuration['storage']['pid'])) {
            throw new \UnexpectedValueException('Importer storage could not be empty', 1571379428146);
        }

        $pids = is_array($configuration['storage']['pid'])
            ? array_map('intval', $configuration['storage']['pid'])
            : GeneralUtility::intExplode(',', $configuration['storage']['pid']);
        $recursive = intval($configuration['storage']['recursive'] ?? 0);

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
                throw new \UnexpectedValueException('New records pid need to be part of storage pids.', 1571391396860);
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
        $repository = ClassNamingUtility::translateModelNameToRepositoryName($domainModel);
        if (!class_exists($repository)) {
            throw new \RuntimeException("Repository '$repository' doesn't exist", 1571382879964);
        }

        $this->repository = $this->objectManager->get($repository);

        // Set table of domain model
        $this->dbTable = ExtbaseUtility::convertClassNameToTableName($domainModel);
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
        return HashUtility::hashImportId($id);
    }

    /**
     * Return DB row with record by import ID and language
     *
     * @param string $hash
     * @param int $language
     * @return array|null
     */
    protected function findRecordByImportIdHash(string $hash, int $language = 0): ?array
    {
        return $this->importRecordRepository->findByImportIdHash(
            $hash,
            $this->dbTable,
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
        return ExtbaseUtility::mapRecord($row, $this->modelName);
    }

    /**
     * Add import data to entity
     *
     * @param AbstractEntity $entity
     * @param array $record
     * @param array $importRow
     * @return void
     */
    protected function populateEntityWithImportData(
        AbstractEntity $entity,
        array $record,
        array $importRow
    ): void {
        foreach ($this->mapping as $field => $mapping) {
            // Get value from import row
            try {
                $value = $this->getFieldMappingValue($field, $importRow);
            } catch (MissingImportField $exception) {
                $this->logger->warning("Missing import value for field '$field'");
                continue;
            }

            $property = $mapping['property'];
            // If processor is set, it should set value for model property
            if (!empty($mapping['processor'])) {
                $processor = $this->createProcessor($mapping);
                $processor->init($entity, $record, $property, $mapping['configuration']);

                if ($processor instanceof PreProcessorInterface) {
                    $value = $processor->preProcess($value);
                }

                $processor->process($value);
            } else {
                // Just set it if no processor
                $currentValue = ObjectAccess::getProperty($entity, $property);
                if ($currentValue != $value) {
                    ObjectAccess::setProperty($entity, $property, $value);
                }
            }
        }
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
     * Get value from import row
     *
     * @param string $field
     * @param array $importRow
     * @return array
     * @throws MissingImportField
     */
    protected function getFieldMappingValue(string $field, array $importRow)
    {
        if (!array_key_exists($field, $importRow)) {
            throw new MissingImportField("Missing import data for mapping field '$field'", 1573197291335);
        }

        return $importRow[$field];
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

        $this->createNewEmptyRecord($id, $language);

        // Get new empty record
        $record = $this->findRecordByImportIdHash($hash, $language);

        if ($record === null) {
            // @codingStandardsIgnoreStart
            throw new \RuntimeException('Error fetching new created record. This should never happen.', 1536063924811);
            // @codingStandardsIgnoreEnd
        }

        return $record;
    }

    /**
     * Create new empty record
     *
     * @param string $id
     * @param int $language
     */
    protected function createNewEmptyRecord(string $id, int $language): void
    {
        $this->importRecordRepository->createEmpty($id, $this->dbTable, $language, $this->defaultNewRecordFields);
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
                $record = $this->findRecordByImportIdHash($hash, $language);

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
        $defaultLanguageRecord = $this->findRecordByImportIdHash($hash, 0);
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

                // Even if there are errors, check if localization was created.
                // There might be errors about localization already exist,
                // because of nested subcategories 1:n relation in product manager
                $recordLocalizations = BackendUtility::getRecordLocalization(
                    $this->dbTable,
                    (int)$defaultLanguageRecord['uid'],
                    $language,
                    'AND pid=' . (int)$defaultLanguageRecord['pid']
                );
                // If there no localization record, return LOCALIZATION_FAILED
                if (empty($recordLocalizations)) {
                    return self::LOCALIZATION_FAILED;
                }
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

        $this->importRecordRepository->delete($uid, $this->dbTable);
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

                if (!$this->adapter->includeRow($key, $rawRow, $language)) {
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
                $record = $this->findRecordByImportIdHash($idHash, $language);

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
                    if ($record !== null) {
                        $this->logger->info(sprintf(
                            'New record created [ID-"%s", UID-"%d", LANG-"%d", TABLE-"%s"].',
                            $id,
                            $record['uid'],
                            $language,
                            $this->dbTable
                        ));

                        $isNew = true;
                    }

                    // Not allowed to create, go to next
                    if (!$isNew) {
                        continue;
                    }
                }

                $entity = $this->mapRow($record);

                // Validate import row
                if (!$this->validator->isValid($row)) {
                    $validationResult = $this->validator->getLastValidationResult();
                    $this->logger->error(sprintf(
                        '%s [ID-"%s", UID-"%s"].',
                        $validationResult->getError(),
                        $id,
                        $record['uid']
                    ));

                    $this->emitSignal(__CLASS__, 'failedValidation', [$entity, $record, $row]);

                    continue;
                }

                // After it's ready for populating data it can be updated/deleted
                $action = $this->detectImportEntityAction($entity, $record, $row, $isNew);

                try {
                    // By default 'updateEntityAction'
                    $this->$action($entity, $record, $row, $isNew);
                } catch (\Exception $exception) {
                    $this->logger->error(sprintf(
                        'Failed import entity [ID-"%s", UID-"%s", REASON-"%s"].',
                        $id,
                        $record['uid'],
                        $exception->getMessage()
                    ));

                    if ($isNew) {
                        $this->deleteNewRecord($record['uid']);
                    }

                    $this->emitSignal(__CLASS__, 'failedPopulating', [$entity, $record, $isNew]);

                    throw $exception;
                }
            }

            $this->persistAndClear();
        }
    }

    /**
     * Persist all objects and clear persistence session
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
     * Run update process for single entity
     *
     * @param AbstractEntity $entity
     * @param array $record
     * @param array $importRow
     * @param bool $isNew
     * @throws \Exception
     */
    protected function updateEntityAction(AbstractEntity $entity, array $record, array $importRow, bool $isNew): void
    {
        $this->populateEntityWithImportData($entity, $record, $importRow);
        $this->persistSingleEntity($entity, $record, $isNew);
    }

    /**
     * Save changes for single entity
     *
     * @param AbstractEntity $entity
     * @param array $record
     * @param bool $isNew
     */
    protected function persistSingleEntity(AbstractEntity $entity, array $record, bool $isNew): void
    {
        $this->emitSignal(__CLASS__, 'beforePersist', [$entity]);

        // Enable if is placeholder
        if ($this->isPlaceholderRecord($record) && $entity->_getProperty('hidden')) {
            $this->removePlaceholderFlag($record['uid']);
            $entity->_setProperty('hidden', false);
        }

        if ($entity->_isDirty()) {
            $this->logger->info(sprintf(
                'Update record [ID-"%s", UID-"%s", TABLE-"%s"]',
                $record[self::DB_IMPORT_ID_FIELD],
                $record['uid'],
                $this->dbTable
            ));

            $this->repository->update($entity);

            if ($isNew) {
                $this->newUids[] = $record['uid'];
            } else {
                $this->updatedUids[] = $record['uid'];
            }

            $this->emitSignal(__CLASS__, 'afterPersist', [$entity]);
        }
    }

    /**
     * Check if record was created as placeholder
     *
     * @param array $record
     * @return bool
     */
    protected function isPlaceholderRecord(array $record): bool
    {
        return (bool)$record[self::DB_IMPORT_PLACEHOLDER];
    }

    /**
     * Mark as non placeholder
     *
     * @param int $uid
     */
    protected function removePlaceholderFlag(int $uid): void
    {
        $this->importRecordRepository->update($uid, $this->dbTable, [self::DB_IMPORT_PLACEHOLDER => 0]);
    }

    /**
     * Check if ID was already in import
     *
     * @param string $idHash
     * @param string $id
     * @return bool False if not unique
     */
    protected function checkIfIdentifierUnique(string $idHash, string $id): bool
    {
        if (in_array($idHash, $this->identifiers)) {
            // @TODO does this mean that it records need to be update again?
            // Persist, we need to save changes done before
            $this->persistenceManager->persistAll();

            $this->logger->notice("Duplicated identifier[ID-'$id']");

            return false;
        } else {
            $this->identifiers[] = $idHash;

            return true;
        }
    }

    /**
     * This method allow to change what action should apply to import entity.
     * By default it's always update. This can be modify in child importers
     *
     * @param AbstractEntity $entity
     * @param array $dbRow
     * @param array $importRow
     * @param bool $isNew
     * @return string
     */
    protected function detectImportEntityAction(
        AbstractEntity $entity,
        array $dbRow,
        array $importRow,
        bool $isNew
    ): string {
        return 'updateEntityAction';
    }

    /**
     * @param array $configuration
     */
    protected function initializeValidator(array $configuration): void
    {
        $this->validator = $this->objectManager->get(ValidationManager::class, $configuration['validation'] ?? []);
    }

    /**
     * Clear cache if tags were provided and import made changes
     */
    protected function clearCache(): void
    {
        $cacheTags = $this->configuration['cacheTags'] ?? [];

        if (!empty($cacheTags) && $this->changesWereMadeByImport()) {
            $this->getCacheService()->flushByTags($cacheTags);
        }
    }

    /**
     * Check if new/update items exist
     *
     * @return bool
     */
    protected function changesWereMadeByImport(): bool
    {
        return !empty($this->newUids) || !empty($this->updatedUids);
    }

    /**
     * @return CacheService
     */
    protected function getCacheService(): CacheService
    {
        return $this->objectManager->get(CacheService::class);
    }
}
