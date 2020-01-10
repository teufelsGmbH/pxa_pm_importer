<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Utility;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\DomainObject\AbstractValueObject;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

/**
 * @package Pixelant\PxaPmImporter\Utility
 */
class ExtbaseUtility
{
    /**
     * @var DataMapper
     */
    protected static $dataMapper = null;

    /**
     * Keep extbase class mapping
     *
     * @var array
     */
    protected static $columnsMappingConfiguration = [];

    /**
     * Convert db raw row to extbase model
     *
     * @param array $row
     * @param string $model
     * @return null|AbstractEntity
     */
    public static function mapRecord(array $row, string $model): AbstractEntity
    {
        $result = static::getDataMapper()->map($model, [$row]);

        return $result[0];
    }

    /**
     * Return table name where model is mapped
     *
     * @param string $model
     * @return string
     */
    public static function convertClassNameToTableName(string $model): string
    {
        return static::getDataMapper()->convertClassNameToTableName($model);
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
        $mapping = static::getColumnsMappingConfiguration($className);

        if (!empty($mapping[$columnName]['mapOnProperty'])) {
            return $mapping[$columnName]['mapOnProperty'];
        }

        return GeneralUtility::underscoredToLowerCamelCase($columnName);
    }

    /**
     * Return extbase model class mapping
     *
     * @param string $className
     * @return array
     */
    protected static function getColumnsMappingConfiguration(string $className): array
    {
        if (isset(static::$columnsMappingConfiguration[$className])) {
            return static::$columnsMappingConfiguration[$className];
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

        static::$columnsMappingConfiguration[$className] = $columnMapping;
        return $columnMapping;
    }

    /**
     * @return DataMapper
     */
    protected static function getDataMapper(): DataMapper
    {
        if (static::$dataMapper === null) {
            static::$dataMapper = GeneralUtility::makeInstance(ObjectManager::class)->get(DataMapper::class);
        }

        return static::$dataMapper;
    }
}
