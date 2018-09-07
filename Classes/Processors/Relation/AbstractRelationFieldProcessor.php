<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaPmImporter\Exception\PostponeProcessorException;
use Pixelant\PxaPmImporter\Processors\AbstractFieldProcessor;
use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\Repository;
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
     * @var Repository
     */
    protected $repository = null;

    /**
     * @var AbstractEntity[]
     */
    protected $entities = [];

    /**
     * Check if category exist
     *
     * @param mixed $value
     */
    public function preProcess(&$value): void
    {
        if (!is_string($value)) {
            $value = (string)$value;
        }
        parent::preProcess($value);
        $value = strtolower($value);

        $this->entities = []; // Reset categories
        $value = GeneralUtility::trimExplode(',', $value, true);

        foreach ($value as $identifier) {
            if (true === (bool)$this->configuration['treatAsIdentifierAsUid']) {
                $model = $this->repository->findByUid((int)$identifier);
            } else {
                $record = $this->getRecord($identifier); // Default language record
                if ($record !== null) {
                    $model = MainUtility::convertRecordArrayToModel($record, $this->getModelClassName());
                }
            }

            if (isset($model) && is_object($model)) {
                $this->entities[] = $model;
            } else {
                // @codingStandardsIgnoreStart
                throw new PostponeProcessorException('Record with id "' . $identifier . '" in table "' . $this->getDbTable() . '" not found for main record with id "' . $record[ImporterInterface::DB_IMPORT_ID_FIELD] . '".', 1536148407513);
                // @codingStandardsIgnoreEnd
            }
        }
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
     * Fetch record
     *
     * @param string $identifier
     * @return array|null
     */
    protected function getRecord(string $identifier): ?array
    {
        $hash = MainUtility::getImportIdHash($identifier);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->getDbTable());
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $row = $queryBuilder
            ->select('*')
            ->from($this->getDbTable())
            ->where(
                $queryBuilder->expr()->eq(
                    ImporterInterface::DB_IMPORT_ID_HASH_FIELD,
                    $queryBuilder->createNamedParameter($hash, Connection::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($this->importer->getPid(), Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * Return repository
     *
     * @return Repository
     */
    abstract protected function getRepository(): Repository;

    /**
     * Table name of current record
     *
     * @return string
     */
    abstract protected function getDbTable(): string;

    /**
     * Name of model to map record
     *
     * @return string
     */
    abstract protected function getModelClassName(): string;
}
