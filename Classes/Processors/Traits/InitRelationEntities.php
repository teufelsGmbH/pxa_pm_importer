<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Traits;

use Pixelant\PxaPmImporter\Exception\PostponeProcessorException;
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
    /**
     * Init entities
     *
     * @param string $value
     * @param string $table
     * @param string $model
     * @return AbstractEntity[]
     */
    protected function initEntitiesForTable(
        string $value,
        string $table,
        string $model,
        \Closure $createNewRecord = null
    ): array {
        $entities = [];
        $value = GeneralUtility::trimExplode(',', $value, true);

        foreach ($value as $identifier) {
            $record = $this->treatIdentifierAsUid()
                ? BackendUtility::getRecord($table, $identifier)
                : $this->getRecordByImportIdentifier($identifier, $table);
            if ($record !== null) {
                $model = MainUtility::convertRecordArrayToModel($record, $model);
            }
            if (isset($model) && is_object($model)) {
                $entities[] = $model;
            } else {
                // @codingStandardsIgnoreStart
                throw new PostponeProcessorException('Product with id "' . $identifier . '" not found.', 1536148407513);
                // @codingStandardsIgnoreEnd
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