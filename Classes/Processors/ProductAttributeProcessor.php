<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Domain\Repository\ImportOptionRepository;
use Pixelant\PxaPmImporter\Exception\InvalidProcessorConfigurationException;
use Pixelant\PxaPmImporter\Processors\Relation\Updater\RelationPropertyUpdater;
use Pixelant\PxaPmImporter\Processors\Traits\FilesResources;
use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use Pixelant\PxaPmImporter\Utility\ExtbaseUtility;
use Pixelant\PxaPmImporter\Utility\HashUtility;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use Pixelant\PxaProductManager\Domain\Model\Attribute;
use Pixelant\PxaProductManager\Domain\Model\AttributeFalFile;
use Pixelant\PxaProductManager\Domain\Model\AttributeValue;
use Pixelant\PxaProductManager\Domain\Model\Product;
use Pixelant\PxaProductManager\Domain\Repository\AttributeRepository;
use Pixelant\PxaProductManager\Utility\TCAUtility;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ProductAttributeProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class ProductAttributeProcessor extends AbstractFieldProcessor
{
    use FilesResources;

    /**
     * @var Attribute
     */
    protected $attribute = null;

    /**
     * @var AttributeRepository
     */
    protected $attributeRepository = null;

    /**
     * @var ImportOptionRepository
     */
    protected $optionsRepository = null;

    /**
     * @var Product
     */
    protected $entity = null;

    /**
     * @var RelationPropertyUpdater
     */
    protected $propertyUpdater = null;

    /**
     * Initialize
     * @param RelationPropertyUpdater $propertyUpdater
     * @param ImportOptionRepository $optionRepository
     */
    public function __construct(RelationPropertyUpdater $propertyUpdater, ImportOptionRepository $optionRepository)
    {
        $this->propertyUpdater = $propertyUpdater;
        $this->optionsRepository = $optionRepository;
    }

    /**
     * @param AttributeRepository $attributeRepository
     */
    public function injectAttributeRepository(AttributeRepository $attributeRepository)
    {
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Process attribute value
     *
     * @param $value
     */
    public function process($value): void
    {
        $this->initAttribute();

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
                $attributeValues[$this->attribute->getUid()] = $this->getOptions($value);
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
                $attributeValues[$this->attribute->getUid()] = $this->importDateTimeString($value);
                break;
            case Attribute::ATTRIBUTE_TYPE_IMAGE:
            case Attribute::ATTRIBUTE_TYPE_FILE:
                $this->updateAttributeFilesReference($value);
                break;
            default:
                throw new \UnexpectedValueException(
                    "Attribute import with type '{$this->attribute->getType()}' is not supported",
                    1536566015842
                );
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

        $attributeValue = GeneralUtility::makeInstance(AttributeValue::class);
        $attributeValue->setPid($this->entity->getPid());
        $attributeValue->setValue($value);
        $attributeValue->setAttribute($this->attribute);

        $this->entity->addAttributeValue($attributeValue);
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
     * Return string date for attribute
     *
     * @param $value
     * @return string
     */
    protected function importDateTimeString($value): string
    {
        if (empty($value)) {
            return '';
        }

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

            return '';
        }

        return $date->format('Y-m-d\TH:i:s\Z');
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
     *
     * @param string|array $value
     * @return string
     */
    protected function getOptions($value): string
    {
        $values = MainUtility::convertListToArray($value);

        $options = $this->optionsRepository->findByHashesAttribute(
            array_map(function ($value) {
                return HashUtility::hashImportId($value);
            }, $values),
            $this->attribute->getUid()
        );

        return implode(',', array_column($options, 'uid'));
    }

    /**
     * Update attribute file reference
     *
     * @param string|array $value Array of file list or comma separated list
     * @return void
     */
    protected function updateAttributeFilesReference($value): void
    {
        $value = MainUtility::convertListToArray($value);
        try {
            $folder = $this->getFolder();
        } catch (FolderDoesNotExistException $exception) {
            $this->logger->error($exception->getMessage());
            return;
        }

        list($currentAttributeFiles, $attributesFiles) = $this->distinguishCurrentAttributeFilesFromRest();

        /**
         * Go thought all import files and create new file reference if files doesn't exist
         * in "$currentAttributeFiles", this means it need to be attached to product as attribute file.
         * If file already has file reference - use it
         */
        // Found files to attach
        $importFiles = $this->collectFilesFromList($folder, $value, $this->logger);
        foreach ($importFiles as $importFile) {
            // Create new file reference
            if (!array_key_exists($importFile->getUid(), $currentAttributeFiles)) {
                /** @var AttributeFalFile $fileReference */
                $fileReference = $this->createFileReference(
                    $importFile,
                    $this->entity->getUid(),
                    $this->context->getNewRecordsPid(),
                    $this->entity->_getProperty('_languageUid'),
                    AttributeFalFile::class
                );
                $fileReference->setAttribute($this->attribute->getUid());

                $attributesFiles[] = $fileReference;
            } else {
                // Use existing file reference
                $attributesFiles[] = $currentAttributeFiles[$importFile->getUid()];
            }
        }

        // Finally ready to update
        $this->propertyUpdater->update(
            $this->entity,
            GeneralUtility::underscoredToLowerCamelCase(TCAUtility::ATTRIBUTE_FAL_FIELD_NAME),
            $attributesFiles
        );
    }

    /**
     * Collect files that belong to current attribute and rest of the files
     *
     * @return array Array of current attribute files and other attributes
     */
    protected function distinguishCurrentAttributeFilesFromRest(): array
    {
        $currentAttributeFiles = [];
        $attributesFiles = [];

        /** @var AttributeFalFile $attributeFile */
        foreach ($this->entity->getAttributeFiles() as $attributeFile) {
            if ($attributeFile->getAttribute() === $this->attribute->getUid()) {
                $fileUid = $this->propertyUpdater->getEntityUidForCompare($attributeFile);
                // File uid => to File reference
                $currentAttributeFiles[$fileUid] = $attributeFile;
            } else {
                $attributesFiles[] = $attributeFile;
            }
        }

        return [$currentAttributeFiles, $attributesFiles];
    }

    /**
     * Init processing attribute
     */
    protected function initAttribute(): void
    {
        if (empty($this->configuration['attributeUid'])) {
            throw new InvalidProcessorConfigurationException(
                "Missing 'attributeUid' of processor configuration. Name - '{$this->property}'",
                1536325707731
            );
        }

        if ((bool)($this->configuration['treatAttributeUidAsImportUid'] ?? false)) {
            $record = $this->findRecordByImportIdentifier(
                $this->configuration['attributeUid'],
                'tx_pxaproductmanager_domain_model_attribute'
            );

            if ($record !== null) {
                $this->attribute = ExtbaseUtility::mapRecord($record, Attribute::class);
            }
        } else {
            $this->attribute = $this->attributeRepository->findByUid((int)$this->configuration['attributeUid']);
        }

        if ($this->attribute === null) {
            throw new \RuntimeException(
                "Could not find attribute with UID '{$this->configuration['attributeUid']}'",
                1536325896431
            );
        }
    }
}
