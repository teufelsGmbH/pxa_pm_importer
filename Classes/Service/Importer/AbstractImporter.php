<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer;

use Pixelant\PxaPmImporter\Adapter\AdapterInterface;
use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Logging\Logger;
use Pixelant\PxaPmImporter\Service\Source\SourceInterface;
use Pixelant\PxaPmImporter\Traits\EmitSignalTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;

/**
 * Class AbstractImporter
 * @package Pixelant\PxaPmImporter\Service\Importer
 */
abstract class AbstractImporter implements ImporterInterface
{
    use EmitSignalTrait;

    /**
     * DB field name with identifier original value
     */
    const DB_IMPORT_ID_FIELD = 'pm_importer_import_id';

    /**
     * DB field name where import hash stored
     */
    const DB_IMPORT_ID_HASH_FIELD = 'pm_importer_import_id_hash';

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
        die;
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

            $this->mapping[$name] = [
                'property' => $fieldMapping['property'] ?? $name,
                'processor' => $fieldMapping['processor'] ?? false
            ];
        }
    }

    protected function processRowFieldValueUsingMapping(AbstractEntity $entity, string $field, $value)
    {
        if (!isset($this->mapping[$field])) {
            // @codingStandardsIgnoreStart
            throw new \RuntimeException('Mapping configuration for field "' . $field . '" doesn\'t exist.', 1536062044810);
            // @codingStandardsIgnoreEnd
        }

        $mapping = $this->mapping[$field];
        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($mapping,'Debug',16);die;
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
        return md5($id);
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
        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);

        $result = $dataMapper->map($this->modelName, [$row]);
        return $result[0];
    }

    /**
     * Check if there is any changed property
     *
     * @param AbstractEntity $entity
     * @return bool
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\TooDirtyException
     */
    protected function modelHasChanged(AbstractEntity $entity): bool
    {
        foreach ($entity->_getProperties() as $property) {
            if ($entity->_isDirty($property)) {
                return true;
            }
        }

        return false;
    }

    protected function populateModelWithImportData(
        AbstractEntity $model,
        array $record,
        array $importRow,
        int $language
    ): void {
        foreach ($importRow as $field => $value) {

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

    /**
     * Create new empty record
     *
     * @param string $id
     * @param string $idHash
     * @param int $language
     */
    abstract protected function createNewEmptyRecord(string $id, string $idHash, int $language): void;

    /**
     * Actual import
     *
     * @return mixed
     */
    abstract protected function runImport(): void;
}
