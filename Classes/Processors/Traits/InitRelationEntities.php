<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Traits;

use Pixelant\PxaPmImporter\Exception\FailedInitEntityException;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
     * @param string $value
     * @param string $table
     * @param string $domainModelClassName
     * @return AbstractEntity[]
     */
    protected function initEntitiesForTable(
        string $value,
        string $table,
        string $domainModelClassName
    ): array {
        $entities = [];
        $value = $this->convertListToArray($value);

        foreach ($value as $identifier) {
            // If identifier is UID from DB
            if ($this->treatIdentifierAsUid()) {
                $record = BackendUtility::getRecord($table, $identifier);
            } else {
                // If not uid find by import hash
                $record = $this->getRecordByImportIdentifier($identifier, $table);
                // If nothing found try to create?
                if ($record === null && method_exists($this, 'createNewEntity')) {
                    $this->createNewEntity($identifier);
                    $record = $this->getRecordByImportIdentifier($identifier, $table);
                }
            }

            if ($record !== null) {
                $model = MainUtility::convertRecordArrayToModel($record, $domainModelClassName);
            }

            if (isset($model) && is_object($model)) {
                $entities[] = $model;
            } else {
                $failedInitEntityException = new FailedInitEntityException(
                    'Could not find entity record for identifier "' . $identifier . '".',
                    1547189793000
                );
                $failedInitEntityException->setIdentifier($identifier);

                throw $failedInitEntityException;
            }
        }

        return $entities;
    }

    /**
     * Check if should treat with identifier as UID from DB
     *
     * @return bool
     */
    private function treatIdentifierAsUid(): bool
    {
        return (bool)($this->configuration['treatIdentifierAsUid'] ?? false);
    }
}
