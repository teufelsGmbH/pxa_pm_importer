<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer;

use Pixelant\PxaPmImporter\Adapter\AdapterInterface;
use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Logging\Logger;
use Pixelant\PxaPmImporter\Processors\ImportFieldProcessorInterface;
use Pixelant\PxaPmImporter\Service\Source\SourceInterface;
use Pixelant\PxaPmImporter\Traits\EmitSignalTrait;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Object\ObjectManager;
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
     */
    public function __construct()
    {
        $this->logger = Logger::getInstance(__CLASS__);
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
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
        //die;
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
        $this->pid = (int)($configuration['pid'] ?? 0);
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
            throw new \RuntimeException('Identifier could not be null, check your import settings', 1535983109427);
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
        if (!is_array($configuration['mapping']) || empty($configuration['mapping'])) {
            throw new \RuntimeException('No mapping found for importer "' . get_class($this) . '"', 1536054721032);
        }

        foreach ($configuration['mapping'] as $fieldMapping) {
            if (empty($fieldMapping['field'])) {
                // @codingStandardsIgnoreStart
                throw new \RuntimeException('Every mapping field should have "field" name set. Empty was found.', 1536058780669);
                // @codingStandardsIgnoreEnd
            }
            $name = $fieldMapping['field'];
            $fieldConfiguration = $fieldMapping;
            unset($fieldConfiguration['field'], $fieldConfiguration['processor'], $fieldConfiguration['property']);

            $this->mapping[$name] = [
                'property' => $fieldMapping['property'] ?? $name,
                'processor' => $fieldMapping['processor'] ?? false,
                'configuration' => $fieldConfiguration
            ];
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
        $id = trim($row[$this->identifier]);

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
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
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
            if ($field === $this->identifier) {
                // it was already set when create new record
                continue;
            }
            if (!isset($this->mapping[$field])) {
                // @codingStandardsIgnoreStart
                throw new \RuntimeException('Mapping configuration for field "' . $field . '" doesn\'t exist.', 1536062044810);
                // @codingStandardsIgnoreEnd
            }

            $mapping = $this->mapping[$field];
            $property = $mapping['property'];

            // If processor is set, it should set value for model property
            if (!empty($mapping['processor'])) {
                $processor = GeneralUtility::makeInstance($mapping['processor']);
                if (!($processor instanceof ImportFieldProcessorInterface)) {
                    // @codingStandardsIgnoreStart
                    throw new \UnexpectedValueException('Processor "' . $mapping['processor'] . '" should be instance of "ImportFieldProcessorInterface"', 1536128672117);
                    // @codingStandardsIgnoreEnd
                }

                $processor->init($model, $record, $property, $this, $mapping['configuration']);

                $processor->preProcess($value);
                if ($processor->isValid($value)) {
                    $processor->process($value);
                } else {
                    $this->logger->error(sprintf(
                        'Property "%s" validation failed for import ID "%s(hash:%s)", with messages: %s',
                        $property,
                        $record[self::DB_IMPORT_ID_FIELD],
                        $record[self::DB_IMPORT_ID_HASH_FIELD],
                        $processor->getValidationErrorsString()
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
     * Handle localization relation
     *
     * @param array $record
     */
    protected function handleLocalization(array $record): void
    {
        $transOrigPointerField = $GLOBALS['TCA'][$this->dbTable]['ctrl']['transOrigPointerField'];
        // If translation default language record is not set try to find it and set
        if ((int)$record[$transOrigPointerField] === 0) {
            $defaultLanguageRecord = $this->getRecordByImportIdHash($record[self::DB_IMPORT_ID_HASH_FIELD], 0);

            if ($defaultLanguageRecord !== null) {
                GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable($this->dbTable)
                    ->update(
                        $this->dbTable,
                        [$transOrigPointerField => (int)$defaultLanguageRecord['uid']],
                        ['uid' => (int)$record['uid']],
                        [Connection::PARAM_INT]
                    );
            }
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

            // One row per record
            foreach ($data as $row) {
                $id = $this->getImportIdFromRow($row);
                $idHash = $this->getImportIdHash($id);
                $record = $this->getRecordByImportIdHash($idHash, $language);

                if ($record === null) {
                    $this->logger->info(sprintf(
                        'Creating new record for table "%", with ID "%s"',
                        $this->dbTable,
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

                if (is_object($model)) {
                    $result = $this->populateModelWithImportData($model, $record, $row);

                    if ($result === false) {
                        // Skip record where population failed
                        continue;
                    }
                }

                if ($model->_isDirty()) {
                    $this->logger->info(sprintf(
                        'Update record for table "%", with UID "%s"',
                        $this->dbTable,
                        $model->getUid()
                    ));
                    $this->repository->update($model);
                }

                if ($language > 0) {
                    $this->handleLocalization($record);
                }
            }
        }
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
