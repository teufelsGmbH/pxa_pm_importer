<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaPmImporter\Processors\AbstractFieldProcessor;
use Pixelant\PxaPmImporter\Processors\Relation\Updater\RelationPropertyUpdater;
use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use Pixelant\PxaPmImporter\Utility\ExtbaseUtility;
use Pixelant\PxaPmImporter\Utility\HashUtility;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Handle relation like 1:1, 1:n, n:m
 * Work with object storage
 *
 * @package Pixelant\PxaPmImporter\Processors\Relation
 */
abstract class AbstractRelationFieldProcessor extends AbstractFieldProcessor
{
    /**
     * @var RelationPropertyUpdater
     */
    protected $propertyUpdater = null;

    /**
     * Initialize
     * @param RelationPropertyUpdater $propertyUpdater
     */
    public function __construct(RelationPropertyUpdater $propertyUpdater)
    {
        $this->propertyUpdater = $propertyUpdater;
    }

    /**
     * Process update
     *
     * @param $value
     */
    public function process($value): void
    {
        $this->propertyUpdater->update($this->entity, $this->property, $this->initEntities($value));
    }

    /**
     * Init entities
     *
     * @param string|array $value
     * @return AbstractEntity[]`
     */
    protected function initEntities($value): array
    {
        $entities = [];
        $value = MainUtility::convertListToArray($value, $this->delim());
        $table = $this->convertClassNameToTableName($this->domainModel());

        foreach ($value as $identifier) {
            // If identifier is UID from DB
            if ($this->treatIdentifierAsUid()) {
                $record = BackendUtility::getRecord($table, $identifier);
            } else {
                // If not uid find by import hash
                $record = $this->findRecordByImportIdentifier($identifier, $table);
                // If nothing found try to create?
                if ($record === null && $this instanceof AbleCreateMissingEntities) {
                    $this->createMissingEntity($identifier);
                    $record = $this->findRecordByImportIdentifier($identifier, $table);
                }
            }

            if ($record !== null) {
                $entities[] = ExtbaseUtility::mapRecord($record, $this->domainModel());
            } else {
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
     * @param string $domainModelClassName
     * @return string
     */
    protected function convertClassNameToTableName(string $domainModelClassName): string
    {
        return ExtbaseUtility::convertClassNameToTableName($domainModelClassName);
    }

    /**
     * @return string
     */
    protected function tcaHiddenField(): string
    {
        $table = $this->convertClassNameToTableName($this->domainModel());
        return $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
    }

    /**
     * @return string
     */
    protected function tcaLabelField(): string
    {
        $table = $this->convertClassNameToTableName($this->domainModel());
        return $GLOBALS['TCA'][$table]['ctrl']['label'];
    }

    /**
     * Add placeholder field to new record fields
     *
     * @param array $fields
     * @return array
     */
    protected function newRecordFieldsWithPlaceHolder(array $fields): array
    {
        $fields[ImporterInterface::DB_IMPORT_PLACEHOLDER] = 1;
        return $fields;
    }

    /**
     * Return name of target model name
     *
     * @return string
     */
    abstract protected function domainModel(): string;
}
