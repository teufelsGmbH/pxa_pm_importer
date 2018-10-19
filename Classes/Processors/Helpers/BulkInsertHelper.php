<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Helpers;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This is helper class to perform bulk insert
 * in case you can't have all data at once and need to collect it first
 * !!! NOTE this class will not automatically persist data
 *
 * @package Pixelant\PxaPmImporter\Processors\Helpers
 */
class BulkInsertHelper implements SingletonInterface
{
    /**
     * Keep data insert data
     *
     * ['dbTableName' => array $insertRows]
     *
     * @var array
     */
    protected $insertRows = [];

    /**
     * Fields name
     * @var array
     */
    protected $insertFields = [];

    /**
     * Insert types
     *
     * ['dbTableName' => [types]]
     *
     * @var array
     */
    protected $types = [];

    /**
     * Add single row for insert
     *
     * @param string $tableName
     * @param array $row
     */
    public function addRow(string $tableName, array $row): void
    {
        $this->addRows($tableName, [$row]);
    }

    /**
     * Add rows for insert
     *
     * @param string $tableName
     * @param array $rows
     */
    public function addRows(string $tableName, array $rows): void
    {
        if (!isset($this->insertRows[$tableName])) {
            $this->insertRows[$tableName] = [];
            $this->insertFields[$tableName] = array_keys(current($rows));
        }

        $this->insertRows[$tableName] = array_merge($this->insertRows[$tableName], $rows);
    }

    /**
     * Set type for insert query
     *
     * @param string $tableName
     * @param array $types
     */
    public function setTypes(string $tableName, array $types): void
    {
        $this->types[$tableName] = $types;
    }

    /**
     * Bulk insert for table
     *
     * @param string $tableName
     */
    public function persistBulkInsert(string $tableName): void
    {
        if (isset($this->insertRows[$tableName])
            && isset($this->insertFields[$tableName])
        ) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($tableName);

            $connection->bulkInsert(
                $tableName,
                $this->insertRows[$tableName],
                $this->insertFields[$tableName],
                $this->types[$tableName] ?? []
            );

            $this->flushTable($tableName);
        } else {
            throw new \UnexpectedValueException(
                'Expect insertRows, insertFields to be set for table "' . $tableName . '"',
                1539949595873
            );
        }
    }

    /**
     * Flush data for single table
     *
     * @param string $table
     */
    public function flushTable(string $table): void
    {
        if (isset($this->insertFields[$table])) {
            unset($this->insertFields[$table]);
        }
        if (isset($this->types[$table])) {
            unset($this->types[$table]);
        }
        if (isset($this->insertRows[$table])) {
            unset($this->insertRows[$table]);
        }
    }

    /**
     * Flush insert state
     */
    public function flushAll(): void
    {
        $this->insertRows = [];
        $this->types = [];
        $this->insertFields = [];
    }
}
