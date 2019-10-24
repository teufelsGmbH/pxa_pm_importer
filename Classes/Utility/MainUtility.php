<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Utility;

use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\DomainObject\AbstractValueObject;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

/**
 * Class MainUtility
 * @package Pixelant\PxaPmImporter\Utility
 */
class MainUtility
{
    /**
     * Keep extbase class mapping
     *
     * @var array
     */
    protected static $classColumnsMappingConfiguration = [];

    /**
     * Convert db raw row to extbase model
     *
     * @param array $row
     * @param string $model
     * @return null|AbstractEntity
     */
    public static function convertRecordArrayToModel(array $row, string $model): AbstractEntity
    {
        $dataMapper = GeneralUtility::makeInstance(ObjectManager::class)->get(DataMapper::class);

        $result = $dataMapper->map($model, [$row]);

        return $result[0];
    }

    /**
     * Return table name where model is mapped
     *
     * @param string $model
     * @return string
     */
    public static function getTableNameByModelName(string $model): string
    {
        $dataMapper = GeneralUtility::makeInstance(ObjectManager::class)->get(DataMapper::class);

        return $dataMapper->convertClassNameToTableName($model);
    }

    /**
     * Get property name by DB column name
     *
     * @param string $className Model class name
     * @param string $columnName DB column name
     * @return string Property name
     */
    public static function convertColumnNameToPropertyName(string $className, string $columnName): string
    {
        $mapping = static::getClassColumnsMappingConfiguration($className);

        if (!empty($mapping[$columnName]['mapOnProperty'])) {
            return $mapping[$columnName]['mapOnProperty'];
        }

        return GeneralUtility::underscoredToLowerCamelCase($columnName);
    }

    /**
     * Get import id hash
     *
     * @param string $id
     * @return string
     */
    public static function getImportIdHash(string $id): string
    {
        return md5($id);
    }

    /**
     * Convert A to 0, B to 1 and so on
     *
     * @param string $column
     * @return int
     */
    public static function convertAlphabetColumnToNumber(string $column): int
    {
        /// @codingStandardsIgnoreStart
        $alphabet = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        // @codingStandardsIgnoreEnd

        $column = trim($column);

        if (empty($column)) {
            throw new \UnexpectedValueException('Column value could not be empty', 1536221838124);
        }
        $length = strlen($column);
        if ($length > 2) {
            throw new \LengthException('Maximum column value can be 2 chars', 1536221841673);
        }

        if ($length === 1) {
            return array_search(strtoupper($column), $alphabet);
        } else {
            $firstValue = (array_search(strtoupper($column[0]), $alphabet) + 1) * count($alphabet);
            return $firstValue + array_search(strtoupper($column[1]), $alphabet);
        }
    }

    /**
     * Get memory usage
     *
     * @return string
     */
    public static function getMemoryUsage(): string
    {
        $size = memory_get_usage(true);
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];

        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[(int)$i];
    }

    /**
     * Fetch records from DB by import Identifier
     *
     * @param string $id
     * @param string $table
     * @param array $pids
     * @param int $language
     * @return array|null
     */
    public static function getRecordByImportId(string $id, string $table, array $pids, int $language = 0): ?array
    {
        $idHash = static::getImportIdHash($id);

        return static::getRecordByImportIdHash($idHash, $table, $pids, $language);
    }

    /**
     * Fetch records from DB by import hash
     * Respect PID and language
     *
     * @param string $hash
     * @param string $table
     * @param array $pids
     * @param int $language
     * @return array|null
     */
    public static function getRecordByImportIdHash(string $hash, string $table, array $pids, int $language = 0): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $row = $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    ImporterInterface::DB_IMPORT_ID_HASH_FIELD,
                    $queryBuilder->createNamedParameter($hash, Connection::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($language, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createNamedParameter($pids, Connection::PARAM_INT_ARRAY)
                )
            )
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * Return extbase model class mapping
     *
     * @param string $className
     * @return array
     */
    protected static function getClassColumnsMappingConfiguration(string $className): array
    {
        if (isset(static::$classColumnsMappingConfiguration[$className])) {
            return static::$classColumnsMappingConfiguration[$className];
        }

        $frameworkConfiguration = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(ConfigurationManagerInterface::class)
            ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

        $columnMapping = [];
        $classSettings = $frameworkConfiguration['persistence']['classes'][$className] ?? null;
        if ($classSettings !== null) {
            $classHierarchy = array_merge([$className], class_parents($className));
            foreach ($classHierarchy as $currentClassName) {
                if (in_array($currentClassName, [AbstractEntity::class, AbstractValueObject::class])) {
                    break;
                }
                $currentClassSettings = $frameworkConfiguration['persistence']['classes'][$currentClassName];
                if ($currentClassSettings !== null) {
                    if (isset($currentClassSettings['mapping']['columns'])
                        && is_array($currentClassSettings['mapping']['columns'])
                    ) {
                        ArrayUtility::mergeRecursiveWithOverrule(
                            $columnMapping,
                            $currentClassSettings['mapping']['columns'],
                            true,
                            false
                        );
                    }
                }
            }
        }

        static::$classColumnsMappingConfiguration[$className] = $columnMapping;
        return $columnMapping;
    }
}
