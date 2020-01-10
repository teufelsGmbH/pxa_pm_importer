<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Traits;

use Pixelant\PxaPmImporter\Exception\FailedInitEntityException;
use Pixelant\PxaPmImporter\Utility\ExtbaseUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Use to generate array of import entities
 *
 * @package Pixelant\PxaPmImporter\Processors\Traits
 */
trait InitRelationEntities
{
    use ImportListValue;

    /**
     * Init entities
     *
     * @param string|array $value
     * @param string $domainModelClassName
     * @param \Closure $createEntity
     * @return AbstractEntity[]
     * @throws FailedInitEntityException
     */
    protected function getEntities(
        $value,
        string $domainModelClassName,
        \Closure $createEntity = null
    ): array {
        $entities = [];
        $value = $this->convertListToArray($value, $this->delim());
        $table = $this->convertClassNameToTableName($domainModelClassName);

        foreach ($value as $identifier) {
            // If identifier is UID from DB
            if ($this->treatIdentifierAsUid()) {
                $record = BackendUtility::getRecord($table, $identifier);
            } else {
                // If not uid find by import hash
                $record = $this->findRecordByImportIdentifier($identifier, $table);
                // If nothing found try to create?
                if ($record === null && $createEntity !== null) {
                    $createEntity($identifier);
                    $record = $this->findRecordByImportIdentifier($identifier, $table);
                }
            }

            if ($record !== null) {
                $model = ExtbaseUtility::mapRecord($record, $domainModelClassName);
            }

            if (isset($model) && is_object($model)) {
                $entities[] = $model;
            } elseif (!$this->disableExceptionOnFailInitEntity()) {
                $failedInitEntityException = new FailedInitEntityException(
                    "Related item not found [ID-'$identifier', TABLE-'$table']",
                    1547189793000
                );
                $failedInitEntityException->setIdentifier($identifier);

                throw $failedInitEntityException;
            } elseif (property_exists($this, 'logger')) {
                $this->logger->error("Related item not found [ID-'$identifier', TABLE-'$table']");
            }
        }

        return $entities;
    }

    /**
     * Return list value delim
     *
     * @return string
     */
    protected function delim(): string
    {
        return $this->configuration['delim'] ?? ',';
    }

    /**
     * Check if should treat with identifier as UID from DB
     *
     * @return bool
     */
    protected function treatIdentifierAsUid(): bool
    {
        return (bool)($this->configuration['treatIdentifierAsUid'] ?? false);
    }

    /**
     * If it should fail when one of the related items wasn't found
     *
     * @return bool
     */
    protected function disableExceptionOnFailInitEntity(): bool
    {
        return (bool)($this->configuration['disableExceptionOnFailInitEntity'] ?? false);
    }

    /**
     * @param string $domainModelClassName
     * @return string
     */
    protected function convertClassNameToTableName(string $domainModelClassName): string
    {
        return ExtbaseUtility::convertClassNameToTableName($domainModelClassName);
    }
}
