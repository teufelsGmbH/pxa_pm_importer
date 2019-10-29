<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Exception\InvalidProcessorConfigurationException;
use Pixelant\PxaPmImporter\Processors\Traits\FilesResources;
use Pixelant\PxaPmImporter\Processors\Traits\ImportListValue;
use Pixelant\PxaPmImporter\Processors\Traits\UpdateRelationProperty;
use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use Pixelant\PxaProductManager\Domain\Model\Attribute;
use Pixelant\PxaProductManager\Domain\Model\AttributeFalFile;
use Pixelant\PxaProductManager\Domain\Model\AttributeValue;
use Pixelant\PxaProductManager\Domain\Model\Product;
use Pixelant\PxaProductManager\Domain\Repository\AttributeRepository;
use Pixelant\PxaProductManager\Utility\TCAUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ProductAttributeProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class ProductAttributeProcessor extends AbstractFieldProcessor
{
    use UpdateRelationProperty;
    use FilesResources;
    use ImportListValue;

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
     * @param ObjectManager $objectManager
     */
    public function injectObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param AttributeRepository $attributeRepository
     */
    public function injectAttributeRepository(AttributeRepository $attributeRepository)
    {
        $this->attributeRepository = $attributeRepository;
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
                $this->logger->error(sprintf(
                    'Could not parse date [ID-"%s", ATTR-"%s", VALUE-"%s"]',
                    $this->dbRow[ImporterInterface::DB_IMPORT_ID_FIELD],
                    $this->attribute->getIdentifier(),
                    $value
                ));

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
        if ($this->skipAttributeImportProcessing($currentValue, $value)) {
            return;
        }

        switch ($this->attribute->getType()) {
            case Attribute::ATTRIBUTE_TYPE_DROPDOWN:
            case Attribute::ATTRIBUTE_TYPE_MULTISELECT:
                $options = $this->getOptions($value);
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
                $attributeValues[$this->attribute->getUid()] = empty($value)
                    ? ''
                    : $this->parseDateTime($value)->format('Y-m-d\TH:i:s\Z');
                break;
            case Attribute::ATTRIBUTE_TYPE_IMAGE:
            case Attribute::ATTRIBUTE_TYPE_FILE:
                $this->updateAttributeFilesReference($value);
                break;
            default:
                // @codingStandardsIgnoreStart
                throw new \UnexpectedValueException('Attribute import with type "' . $this->attribute->getType() . '" is not supported', 1536566015842);
            // @codingStandardsIgnoreEnd
        }

        // We don't need to save value for fal attribute, since fal reference is already set
        if (false === $this->attribute->isFalType()) {
            $this->entity->setSerializedAttributesValues(serialize($attributeValues));
            $this->updateAttributeValue((string)$attributeValues[$this->attribute->getUid()]);
        }
    }

    /**
     * Check if processing can be skipped for attribute.
     * For example in case if import values is same as current
     *
     * @param $currentValue
     * @param $importValue
     * @return bool
     */
    protected function skipAttributeImportProcessing($currentValue, $importValue): bool
    {
        if ($this->attribute->isFalType()) {
            // Always run import for FAL attributes.
            // Relation processor will check if files are different or no
            return false;
        }

        return $currentValue == $importValue && $this->getAttributeValue() !== null;
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

        $attributeValue = $this->createAttributeValue();
        $attributeValue->setPid($this->context->getNewRecordsPid());
        $attributeValue->setValue($value);
        $attributeValue->setAttribute($this->attribute);

        $this->entity->addAttributeValue($attributeValue);
    }

    /**
     * Create empty attribute value
     *
     * @return AttributeValue
     */
    protected function createAttributeValue(): AttributeValue
    {
        return $this->objectManager->get(AttributeValue::class);
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
    protected function parseDateTime(string $value): ?\DateTime
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
     * @param string|array $value
     * @return array
     */
    protected function getOptions($value): array
    {
        $values = $this->convertListToArray($value);
        $hashes = array_map(
            function ($value) {
                return MainUtility::getImportIdHash($value);
            },
            $values
        );

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_pxaproductmanager_domain_model_option');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $result = $queryBuilder
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
                    $queryBuilder->createNamedParameter($this->context->getStoragePids(), Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'attribute',
                    $queryBuilder->createNamedParameter($this->attribute->getUid(), Connection::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll(\PDO::FETCH_COLUMN);

        return is_array($result) ? $result : [];
    }

    /**
     * Update attribute file reference
     *
     * @param string|array $value Array of file list or comma separated list
     * @return array Values(files) there were attached to the product
     */
    protected function updateAttributeFilesReference($value): array
    {
        $value = $this->convertListToArray($value);
        try {
            $folder = $this->getFolder();
        } catch (FolderDoesNotExistException $exception) {
            $this->logger->error($exception->getMessage());
            return [];
        }

        $attributeFiles = [];
        /**
         * Collect all other attributes values. Since all attributes files are attached to one product field,
         * but distinguish by pxa_attribute, all files should be present on update process for every attribute
         */
        /** @var AttributeFalFile $falReference */
        foreach ($this->entity->getAttributeFiles() as $falReference) {
            // Add all other files that doesn't belong to current attribute, so doesn't get removed on update
            if ($falReference->getAttribute() !== $this->attribute->getUid()) {
                $attributeFiles[] = $falReference;
            }
        }

        /**
         * Collect all existing attribute files
         * in order to be able to reuse existing file reference
         */
        // File uid => to File reference
        $existingAttributeFiles = [];

        /** @var AttributeFalFile $attributeFile */
        foreach ($this->entity->getAttributeFiles() as $attributeFile) {
            if ($attributeFile->getAttribute() === $this->attribute->getUid()) {
                $existingAttributeFiles[$this->getEntityUidForCompare($attributeFile)] = $attributeFile;
            }
        }

        /**
         * Go thought all import files and create new file reference if files doesn't exist
         * in "$existingAttributeFiles", this means it need to be attached to product as attribute file.
         * If file already has file reference - use it
         */
        // Found files to attach
        $attachFiles = $this->collectFilesFromList($folder, $value, $this->logger);
        // Found given values
        $foundValues = array_keys($attachFiles);
        foreach ($attachFiles as $file) {
            // Create new file reference
            if (!array_key_exists($file->getUid(), $existingAttributeFiles)) {
                /** @var AttributeFalFile $fileReference */
                $fileReference = $this->createFileReference(
                    $file,
                    $this->entity->getUid(),
                    $this->context->getNewRecordsPid(),
                    $this->entity->_getProperty('_languageUid'),
                    AttributeFalFile::class
                );
                $fileReference->setAttribute($this->attribute->getUid());

                $attributeFiles[] = $fileReference;
            } else {
                // Use existing file reference
                $attributeFiles[] = $existingAttributeFiles[$file->getUid()];
            }
        }

        // Finally ready to update
        $this->updateRelationProperty(
            $this->entity,
            GeneralUtility::underscoredToLowerCamelCase(TCAUtility::ATTRIBUTE_FAL_FIELD_NAME),
            $attributeFiles
        );

        return $foundValues;
    }
}
