<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class AbstractFieldProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
abstract class AbstractFieldProcessor implements FieldProcessorInterface
{
    /**
     * Field processing configuration
     *
     * @var array
     */
    protected $configuration = [];

    /**
     * Current property
     *
     * @var string
     */
    protected $property = '';

    /**
     * Error messages
     *
     * @var array
     */
    protected $validationErrors = [];


    /**
     * Model that is currently populated
     *
     * @var AbstractEntity
     */
    protected $entity = null;

    /**
     * Original entity DB row
     *
     * @var array
     */
    protected $dbRow = [];

    /**
     * Parent object
     *
     * @var ImporterInterface
     */
    protected $importer = null;

    /**
     * Init
     *
     * @param AbstractEntity $entity
     * @param array $dbRow
     * @param string $property
     * @param ImporterInterface $importer
     * @param array $configuration
     */
    public function init(
        AbstractEntity $entity,
        array $dbRow,
        string $property,
        ImporterInterface $importer,
        array $configuration
    ): void {
        $this->entity = $entity;
        $this->dbRow = $dbRow;
        $this->property = $property;
        $this->importer = $importer;
        $this->configuration = $configuration;
    }

    /**
     * Pretty common for all fields
     *
     * @param mixed &$value
     */
    public function preProcess(&$value): void
    {
        $value = trim($value);
    }

    /**
     * Check if required
     *
     * @param $value
     * @return bool
     */
    public function isValid($value): bool
    {
        if ($this->isRequired() && empty($value)) {
            $this->addError('Property "' . $this->property . '" is required');

            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * @return string
     */
    public function getValidationErrorsString(): string
    {
        return '"' . implode('", "', $this->validationErrors) . '"';
    }

    /**
     * @return AbstractEntity
     */
    public function getProcessingEntity(): AbstractEntity
    {
        return $this->entity;
    }

    /**
     * @return array
     */
    public function getProcessingDbRow(): array
    {
        return $this->dbRow;
    }

    /**
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * @return string
     */
    public function getProcessingProperty(): string
    {
        return $this->property;
    }

    /**
     * Check if field is required
     *
     * @return bool
     */
    protected function isRequired(): bool
    {
        return $this->isRuleInValidationList('required');
    }

    /**
     * Check if validation rule is in list
     *
     * @param string $rule
     * @return bool
     */
    protected function isRuleInValidationList(string $rule): bool
    {
        return GeneralUtility::inList($this->configuration['validation'] ?? '', $rule);
    }

    /**
     * Add validation error
     *
     * @param string $error
     */
    protected function addError(string $error): void
    {
        $this->validationErrors[] = $error;
    }

    /**
     * Set entity properties like strings, numbers, etc..
     *
     * @param $value
     */
    protected function simplePropertySet($value)
    {
        // Setter for simple values
        $currentValue = ObjectAccess::getProperty($this->entity, $this->property);
        if ($currentValue !== $value) {
            ObjectAccess::setProperty($this->entity, $this->property, $value);
        }
    }

    /**
     * Add update object storage with import items, remove that items are not in a list
     *
     * @param ObjectStorage $storage
     * @param AbstractEntity[] $importEntities
     */
    protected function updateObjectStorage(ObjectStorage $storage, array $importEntities): void
    {
        // Get uids of all from import
        $importEntitiesUids = [];
        /** @var AbstractEntity $entity */
        foreach ($importEntities as $entity) {
            $importEntitiesUids[] = $entity->getUid();
        }

        // Collect that already in storage, remove that are not in import value
        $entitiesInStorageUids = [];
        /** @var AbstractEntity $storageItem */
        foreach ($storage->toArray() as $storageItem) {
            if (!in_array($storageItem->getUid(), $importEntitiesUids)) {
                $storage->detach($storageItem);
            } else {
                $entitiesInStorageUids[] = $storageItem->getUid();
            }
        }

        /** @var AbstractEntity $entity */
        foreach ($importEntities as $entity) {
            if (!in_array($entity->getUid(), $entitiesInStorageUids)) {
                $storage->attach($entity);
            }
        }
    }

    /**
     * Update property value that has relation 1:1 or object storage
     *
     * @param array $importEntities
     */
    protected function updateRelationProperty(array $importEntities): void
    {
        $propertyValue = ObjectAccess::getProperty($this->entity, $this->property);
        /** @var AbstractEntity $firstEntity */
        $firstEntity = $importEntities[0] ?? false;

        // If nothing is set
        // or property isn't object storage and need to be updated
        // 1:1 Relation
        if (is_object($firstEntity)
            && ($propertyValue === null
                || ($propertyValue instanceof AbstractEntity && $propertyValue->getUid() !== $firstEntity->getUid())
            )
        ) {
            ObjectAccess::setProperty($this->entity, $this->property, $firstEntity);
            return;
        }

        // Multiple relation
        if (is_object($propertyValue) && $propertyValue instanceof ObjectStorage) {
            $this->updateObjectStorage($propertyValue, $importEntities);

            return;
        }
    }
}
