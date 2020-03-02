<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation\Updater;

use Pixelant\PxaPmImporter\Domain\Repository\FileReferenceRepository;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Handle updating of property relation
 *
 * @package Pixelant\PxaPmImporter\Processors\Relation\Updater
 */
class RelationPropertyUpdater
{
    /**
     * @var FileReferenceRepository
     */
    protected $referenceRepository;

    /**
     * @param FileReferenceRepository $referenceRepository
     */
    public function __construct(FileReferenceRepository $referenceRepository)
    {
        $this->referenceRepository = $referenceRepository;
    }

    /**
     * Update property value that has relation 1:1 or object storage
     *
     * @param AbstractEntity $entity
     * @param string $property
     * @param AbstractEntity[] $importEntities
     */
    public function update(AbstractEntity $entity, string $property, array $importEntities): void
    {
        $propertyValue = ObjectAccess::getProperty($entity, $property);
        if ($propertyValue instanceof LazyLoadingProxy) {
            $propertyValue = $propertyValue->_loadRealInstance();
        }

        $firstEntity = $importEntities[0] ?? null;

        // If already has some values
        if (is_object($propertyValue)) {
            // If  object storage
            if ($propertyValue instanceof ObjectStorage) {
                $this->updateObjectStorage($entity, $property, $propertyValue, $importEntities);
            }
            // If relation 1:1
            if ($propertyValue instanceof AbstractEntity
                && (
                    $firstEntity === null
                    || $this->getEntityUidForCompare($firstEntity) !== $propertyValue->getUid()
                )
            ) {
                // If entity is null we need to use direct access, because it's high risk that
                // setter method except only objects, but we need to reset value
                ObjectAccess::setProperty($entity, $property, $firstEntity, $firstEntity === null);

                // If old value is file reference, we need to delete it, extbase won't do it.
                if ($propertyValue instanceof FileReference) {
                    $this->referenceRepository->remove($propertyValue);
                }
            }
        } elseif ($firstEntity !== null) {
            ObjectAccess::setProperty($entity, $property, $firstEntity);
        }
    }

    /**
     * Get uid that will be used for check if property value has different UID and need to be updated
     *
     * @param AbstractEntity $entity
     * @return int
     */
    public function getEntityUidForCompare(AbstractEntity $entity): int
    {
        // If this is file entity use File UID
        if ($entity instanceof FileReference) {
            return $entity->getOriginalResource()->getOriginalFile()->getUid();
        }

        return $entity->getUid();
    }

    /**
     * Add update object storage with import items, remove that items are not in a list
     * Update domain model relation like 1:1, 1:m, m:n
     *
     * @param AbstractEntity $processingEntity
     * @param string $property
     * @param ObjectStorage $storage
     * @param AbstractEntity[] $importEntities
     */
    protected function updateObjectStorage(
        AbstractEntity $processingEntity,
        string $property,
        ObjectStorage $storage,
        array $importEntities
    ): void {
        if ($this->doesStorageDiff($storage, $importEntities)) {
            $newStorage = new ObjectStorage();

            foreach ($importEntities as $entity) {
                $newStorage->attach($entity);
            }

            ObjectAccess::setProperty($processingEntity, $property, $newStorage);
        }
    }

    /**
     * Check if object storage has different items than import entities and need to be replaced
     * Or if order changed
     *
     * @param ObjectStorage $objectStorage
     * @param array $entities
     * @return bool
     */
    protected function doesStorageDiff(ObjectStorage $objectStorage, array $entities): bool
    {
        $storageUids = array_map(
            function ($item) {
                return $this->getEntityUidForCompare($item);
            },
            $objectStorage->toArray()
        );
        $entitiesUids = array_map(
            function ($item) {
                return $this->getEntityUidForCompare($item);
            },
            $entities
        );

        // Since it has only integer uids - compare
        return $storageUids !== $entitiesUids;
    }
}
