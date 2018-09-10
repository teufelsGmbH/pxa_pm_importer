<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaPmImporter\Processors\AbstractFieldProcessor;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * This class created to help handle relation like 1:1, 1:n, n:m
 * Work with object storage
 *
 * @package Pixelant\PxaPmImporter\Processors\Relation
 */
abstract class AbstractRelationFieldProcessor extends AbstractFieldProcessor
{
    /**
     * @var AbstractEntity[]
     */
    protected $entities = [];

    /**
     * Call init entities method
     *
     * @param mixed $value
     */
    public function preProcess(&$value): void
    {
        if (!is_string($value)) {
            $value = (string)$value;
        }
        parent::preProcess($value);
        
        $this->initEntities($value);
    }

    /**
     * @param $value
     */
    public function process($value): void
    {
        $this->updateRelationProperty($this->entities);
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

    /**
     * This method should prepare entities for later call in process
     *
     * @param $value
     */
    abstract protected function initEntities($value): void;
}
