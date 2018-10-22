<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Exception\InvalidProcessorConfigurationException;
use Pixelant\PxaPmImporter\Logging\Logger;
use Pixelant\PxaPmImporter\Processors\Helpers\BulkInsertHelper;
use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use Pixelant\PxaPmImporter\Traits\EmitSignalTrait;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use Pixelant\PxaProductManager\Domain\Model\Attribute;
use Pixelant\PxaProductManager\Domain\Model\AttributeValue;
use Pixelant\PxaProductManager\Domain\Model\Product;
use Pixelant\PxaProductManager\Domain\Repository\AttributeRepository;
use Pixelant\PxaProductManager\Utility\TCAUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ProductAttributeProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class ProductAttributeProcessor extends AbstractFieldProcessor
{
    use EmitSignalTrait;

    /**
     * @var Attribute
     */
    protected $attribute = null;

    /**
     * @var ObjectManager
     */
    protected $objectManager = null;

    /**
     * @var AttributeRepository
     */
    protected $attributeRepository = null;

    /**
     * @var Product
     */
    protected $entity = null;

    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->logger = Logger::getInstance(__CLASS__);
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->attributeRepository = $this->objectManager->get(AttributeRepository::class);
    }

    /**
     * Prepare for process
     *
     * @param mixed $value
     */
    public function preProcess(&$value): void
    {
        if (empty($this->configuration['attributeUid'])) {
            // @codingStandardsIgnoreStart
            throw new InvalidProcessorConfigurationException('Missing "attributeUid" of processor configuration. Name - "' . $this->property . '"', 1536325707731);
            // @codingStandardsIgnoreEnd
        }

        if (isset($this->configuration['treatAttributeUidAsImportUid'])
            && (bool)$this->configuration['treatAttributeUidAsImportUid'] === true
        ) {
            $record = $this->getRecordByImportIdentifier(
                $this->configuration['attributeUid'],
                'tx_pxaproductmanager_domain_model_attribute'
            );

            if ($record !== null) {
                $this->attribute = MainUtility::convertRecordArrayToModel($record, Attribute::class);
            }
        } else {
            $this->attribute = $this->attributeRepository->findByUid((int)$this->configuration['attributeUid']);
        }

        if ($this->attribute === null) {
            // @codingStandardsIgnoreStart
            throw new \RuntimeException('Could not find attribute with UID "' . $this->configuration['attributeUid'] . '"', 1536325896431);
            // @codingStandardsIgnoreEnd
        }

        parent::preProcess($value);
    }

    /**
     * Additional validation
     *
     * @param $value
     * @return bool
     */
    public function isValid($value): bool
    {
        if ($this->attribute->getType() === Attribute::ATTRIBUTE_TYPE_DATETIME && !empty($value)) {
            try {
                $date = $this->parseDateTime($value);
            } catch (\Exception $exception) {
                $date = null;
            }

            if ($date === null) {
                $this->addError('Could not parse date from "' . $value . '" for "' . $this->property . '"');
                return false;
            }
        }

        return parent::isValid($value);
    }

    /**
     * Process attribute value
     *
     * @param $value
     */
    public function process($value): void
    {
        $attributeValues = unserialize($this->entity->getSerializedAttributesValues()) ?: [];
        $currentValue = $attributeValues[$this->attribute->getUid()] ?? '';

        // If value is same and attribute value record exist
        // Fal type doesn't have attribute values
        if ($currentValue == $value && ($this->attribute->isFalType() || $this->getAttributeValue() !== null)) {
            return;
        }

        switch ($this->attribute->getType()) {
            case Attribute::ATTRIBUTE_TYPE_DROPDOWN:
            case Attribute::ATTRIBUTE_TYPE_MULTISELECT:
                $options = $this->getOptions((string)$value);
                $attributeValues[$this->attribute->getUid()] = implode(',', $options);
                break;
            case Attribute::ATTRIBUTE_TYPE_CHECKBOX:
                $attributeValues[$this->attribute->getUid()] = ((bool)$value) ? 1 : 0;
                break;
            case Attribute::ATTRIBUTE_TYPE_INPUT:
            case Attribute::ATTRIBUTE_TYPE_LABEL:
            case Attribute::ATTRIBUTE_TYPE_TEXT:
            case Attribute::ATTRIBUTE_TYPE_LINK:
                $attributeValues[$this->attribute->getUid()] = (string)$value;
                break;
            case Attribute::ATTRIBUTE_TYPE_DATETIME:
                $value = empty($value)
                    ? ''
                    : $this->parseDateTime($value);
                $attributeValues[$this->attribute->getUid()] = $value->format('Y-m-d\TH:i:s\Z');
                break;
            case Attribute::ATTRIBUTE_TYPE_IMAGE:
            case Attribute::ATTRIBUTE_TYPE_FILE:
                $this->updateAttributeFilesReference($value);
                $attributeValues[$this->attribute->getUid()] = (string)$value;
                break;
            default:
                // @codingStandardsIgnoreStart
                throw new \UnexpectedValueException('Attribute import with type "' . $this->attribute->getType() . '" is not supported', 1536566015842);
            // @codingStandardsIgnoreEnd
        }

        $this->entity->setSerializedAttributesValues(serialize($attributeValues));
        // We don't need to save value for fal attribute, since fal reference is already set
        if (false === $this->attribute->isFalType()) {
            $this->updateAttributeValue((string)$attributeValues[$this->attribute->getUid()]);
        }
    }

    /**
     * Update attribute value record
     *
     * @param string $value
     */
    protected function updateAttributeValue(string $value): void
    {
        // Try to find existing attribute value
        if ($attributeValue = $this->getAttributeValue()) {
            $attributeValue->setValue($value);

            return;
        }

        // If not found, create one
        $time = time();
        $bulkInsertHelper = $this->getBulkInsertHelper();
        $bulkInsertHelper->addRow(
            'tx_pxaproductmanager_domain_model_attributevalue',
            [
                'attribute' => $this->attribute->getUid(),
                'product' => intval($this->dbRow['uid']),
                'value' => $value,
                'tstamp' => $time,
                'crdate' => $time,
                'pid' => $this->importer->getPid(),
                't3_origuid' => 0,
                'l10n_parent' => 0,
                'sys_language_uid' => intval($this->dbRow['sys_language_uid'])
            ]
        );
    }

    /**
     * Get attribute value object from current product for current attribute
     *
     * @return null|AttributeValue
     */
    protected function getAttributeValue(): ?AttributeValue
    {
        /** @var AttributeValue $attributeValue */
        foreach ($this->entity->getAttributeValues() as $attributeValue) {
            if ($attributeValue->getAttribute()->getUid() === $this->attribute->getUid()) {
                return $attributeValue;
            }
        }

        return null;
    }

    /**
     * Parse datetime from value
     *
     * @param string $value
     * @return \DateTime|null
     */
    protected function parseDateTime(string $value)
    {
        if (!empty($this->configuration['dateFormat'])) {
            $date = \DateTime::createFromFormat($this->configuration['dateFormat'], $value);
        } else {
            $date = new \DateTime($value);
        }

        return $date;
    }

    /**
     * Fetch options uids
     * Use this query method, since we can fetch it also with hash values
     *
     * @param string $value
     * @return array
     */
    protected function getOptions(string $value): array
    {
        $values = GeneralUtility::trimExplode(',', $value, true);
        unset($value);
        $hashes = [];
        foreach ($values as $value) {
            $hashes[] = MainUtility::getImportIdHash($value);
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_pxaproductmanager_domain_model_option');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $statement = $queryBuilder
            ->select('uid')
            ->from('tx_pxaproductmanager_domain_model_option')
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->in(
                        'value',
                        $queryBuilder->createNamedParameter($values, Connection::PARAM_STR_ARRAY)
                    ),
                    $queryBuilder->expr()->in(
                        ImporterInterface::DB_IMPORT_ID_HASH_FIELD,
                        $queryBuilder->createNamedParameter($hashes, Connection::PARAM_STR_ARRAY)
                    )
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($this->importer->getPid(), Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'attribute',
                    $queryBuilder->createNamedParameter($this->attribute->getUid(), Connection::PARAM_INT)
                )
            )
            ->execute();
        $uids = [];
        while ($row = $statement->fetch()) {
            $uids[] = $row['uid'];
        }

        return $uids;
    }

    /**
     * Update attribute file reference
     *
     * @param string $value
     */
    protected function updateAttributeFilesReference(string $value): void
    {
        $storage = ResourceFactory::getInstance()->getStorageObject((int)($this->configuration['storageUid'] ?? 1));
        try {
            $folder = isset($this->configuration['folder'])
                ? $storage->getFolder($this->configuration['folder'])
                : $storage->getRootLevelFolder();
        } catch (FolderDoesNotExistException $exception) {
            $this->addError($exception->getMessage());
            return;
        }

        $importFiles = [];

        foreach (GeneralUtility::trimExplode(',', $value, true) as $filePath) {
            $fileIdentifier = $folder->getIdentifier() . $filePath;
            $this->emitSignal('beforeImportFileGet', [$fileIdentifier, $this->configuration]);

            if ($storage->hasFile($fileIdentifier)) {
                /** @var File $file */
                $file = $storage->getFile($fileIdentifier);
                $importFiles[] = $file->getUid();
            } else {
                $this->logger->error(sprintf(
                    'File "%s" doesn\'t exist for attribute "%s (UID - %d)"',
                    $fileIdentifier,
                    $this->attribute->getName(),
                    $this->attribute->getUid()
                ));
            }
        }

        $attributeFiles = [];
        /** @var FileReference $falReference */
        foreach ($this->entity->getAttributeFiles()->toArray() as $falReference) {
            $falAttributeUid = (int)$falReference->getOriginalResource()->getReferenceProperty('pxa_attribute');
            if ($falAttributeUid === $this->attribute->getUid()) {
                $attributeFiles[$falReference->getOriginalResource()->getUid()] =
                    $falReference->getOriginalResource()->getOriginalFile()->getUid();
            }
        }

        $missingFiles = array_diff($importFiles, $attributeFiles);
        if (!empty($missingFiles)) {
            $this->createNewFileReferences($missingFiles);
        }

        $deleteFilesReference = [];
        foreach ($attributeFiles as $fileReferenceUid => $fileUid) {
            if (!in_array($fileUid, $importFiles, true)) {
                $deleteFilesReference[] = $fileReferenceUid;
            }
        }
        if (!empty($deleteFilesReference)) {
            $this->removeAttributeFileReference($deleteFilesReference);
        }
    }

    /**
     * Will create new file reference for product and attribute for missing files
     *
     * @param array $fileUids
     */
    protected function createNewFileReferences(array $fileUids): void
    {
        $newRows = [];
        $time = time();
        foreach ($fileUids as $fileUid) {
            $newRows[] = [
                'table_local' => 'sys_file',
                'uid_local' => $fileUid,
                'tablenames' => 'tx_pxaproductmanager_domain_model_product',
                'uid_foreign' => $this->entity->getUid(),
                'sys_language_uid' => (int)$this->dbRow['sys_language_uid'],
                'fieldname' => TCAUtility::ATTRIBUTE_FAL_FIELD_NAME,
                'pxa_attribute' => $this->attribute->getUid(),
                'pid' => $this->importer->getPid(),
                'crdate' => $time,
                'tstamp' => $time,
            ];
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(
            'sys_file_reference'
        );
        $connection->bulkInsert(
            'sys_file_reference',
            $newRows,
            [
                'table_local',
                'uid_local',
                'tablenames',
                'uid_foreign',
                'sys_language_uid',
                'fieldname',
                'pxa_attribute',
                'pid',
                'crdate',
                'tstamp'
            ],
            [
                \PDO::PARAM_STR,
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                \PDO::PARAM_INT
            ]
        );
    }

    /**
     * Mark file reference as deleted
     *
     * @param array $fileReferenceUidsToRemove
     * @return void
     */
    protected function removeAttributeFileReference(array $fileReferenceUidsToRemove): void
    {
        if (empty($fileReferenceUidsToRemove)) {
            return;
        }

        $deleteField = $GLOBALS['TCA']['sys_file_reference']['ctrl']['delete'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');

        $queryBuilder
            ->update('sys_file_reference')
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($fileReferenceUidsToRemove, Connection::PARAM_INT_ARRAY)
                )
            )
            ->set($deleteField, 1)
            ->execute();
    }

    /**
     * @return BulkInsertHelper
     */
    protected function getBulkInsertHelper(): BulkInsertHelper
    {
        return GeneralUtility::makeInstance(BulkInsertHelper::class);
    }
}
