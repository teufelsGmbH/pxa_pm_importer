<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Adapter;

use Pixelant\PxaPmImporter\Adapter\Filters\FilterInterface;
use Pixelant\PxaPmImporter\Service\Source\SourceInterface;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class AbstractDefaultAdapter
 * @package Pixelant\PxaPmImporter\Adapter
 */
abstract class AbstractDefaultAdapter implements AdapterInterface
{
    /**
     * Identifier column
     *
     * @var mixed
     */
    protected $identifier = null;

    /**
     * Mapping configuration for languages
     *
     * @var array
     */
    protected $languagesMapping = null;

    /**
     * Adapter configuration
     *
     * @var array
     */
    protected $settings = [];

    /**
     * Adapter filter configuration
     *
     * @var array
     */
    protected $filters = [];

    /**
     * @var ObjectManager
     */
    protected $objectManager = null;

    /**
     * @param ObjectManager $objectManager
     */
    public function injectObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Initialize default settings
     *
     * @param array $configuration
     */
    public function initialize(array $configuration): void
    {
        if (empty($configuration['mapping'])) {
            throw new \RuntimeException('Adapter mapping configuration is invalid.', 1536050678725);
        }
        $isExcelColumns = isset($configuration['mapping']['excelColumns'])
            ? (bool)$configuration['mapping']['excelColumns']
            : false;

        if (isset($configuration['mapping']['id'])) {
            $this->identifier = $this->getFieldMapping('id', $isExcelColumns, $configuration['mapping']['id']);
        } else {
            throw new \RuntimeException('Adapter mapping require "id" (identifier) mapping to be set.', 1536050717594);
        }

        if (!empty($configuration['mapping']['languages']) && is_array($configuration['mapping']['languages'])) {
            $this->languagesMapping = $configuration['mapping']['languages'];

            if ($isExcelColumns) {
                foreach ($this->languagesMapping as $language => $languageMapping) {
                    foreach ($languageMapping as $field => $mappingRules) {
                        $this->languagesMapping[$language][$field] = $this->getFieldMapping(
                            $field,
                            $isExcelColumns,
                            $mappingRules
                        );
                    }
                }
            }
        } else {
            // @codingStandardsIgnoreStart
            throw new \RuntimeException('Adapter mapping require at least one language mapping configuration.', 1536050795179);
            // @codingStandardsIgnoreEnd
        }

        // Set settings
        if (isset($configuration['settings']) && is_array($configuration['settings'])) {
            $this->settings = $configuration['settings'];
        }

        // Set filters
        if (isset($configuration['filters']) && is_array($configuration['filters'])) {
            $this->filters = $configuration['filters'];
        }
    }

    /**
     * Check if row should be excluded by filter
     *
     * @param mixed $key Row key
     * @param array $dataRow
     * @return boolean
     */
    public function includeRow($key, $dataRow): bool
    {
        if (is_array($this->filters) && count($this->filters) > 0) {
            foreach ($this->filters as $column => $filter) {
                if (!empty($filter['filter'])) {
                    $filterObject = $this->getFilterInstance($filter['filter']);
                    if (!($filterObject instanceof FilterInterface)) {
                        // @codingStandardsIgnoreStart
                        throw new \UnexpectedValueException('Filter "' . $filter['filter'] . '" should be instance of "FilterInterface"', 1538142318);
                        // @codingStandardsIgnoreEnd
                    }
                    if (!$filterObject->includeRow($column, $key, $dataRow, $filter)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * @return array
     */
    public function getImportLanguages(): array
    {
        return array_keys($this->languagesMapping);
    }

    /**
     * Count amount of import items
     * Usually source count multiply by languages
     *
     * @param SourceInterface $source
     * @return int
     */
    public function countAmountOfItems(SourceInterface $source): int
    {
        return count($this->getImportLanguages()) * $source->count();
    }

    /**
     * Get mapping for field
     *
     * @param string $field
     * @param bool $isExcelColumns
     * @param $mappingRules
     * @return array|float|int|string
     */
    protected function getFieldMapping(string $field, bool $isExcelColumns, $mappingRules)
    {
        if (is_numeric($mappingRules) && !is_float($mappingRules)) {
            $mappingResult = (int)$mappingRules;
        } elseif (is_string($mappingRules)) {
            if ($isExcelColumns) {
                $mappingResult = MainUtility::convertAlphabetColumnToNumber($mappingRules);
            } else {
                $mappingResult = $mappingRules;
            }
        } elseif (is_array($mappingRules)) {
            if (count($mappingRules) < 1) {
                // @codingStandardsIgnoreStart
                throw new \UnexpectedValueException('"' . $field . '" field mapping as array should have at least one element.', 1538560400221);
                // @codingStandardsIgnoreEnd
            }

            if ($isExcelColumns) {
                $mappingResult = array_map(
                    function ($item) {
                        return MainUtility::convertAlphabetColumnToNumber($item);
                    },
                    $mappingRules
                );
            } else {
                $mappingResult = $mappingRules;
            }
        }

        if (!isset($mappingResult)) {
            // @codingStandardsIgnoreStart
            throw new \RuntimeException('Could not set maaping for field "' . $field . '". String, numeric and array values are only supported.', 1538560523613);
            // @codingStandardsIgnoreEnd
        }

        return $mappingResult;
    }

    /**
     * @param string $filter
     * @return FilterInterface
     */
    protected function getFilterInstance(string $filter): FilterInterface
    {
        $filterObject = $this->objectManager->get($filter);
        if (!$filterObject instanceof FilterInterface) {
            $type = gettype($filterObject);

            throw new \UnexpectedValueException(
                "Expect filter to be instance of FilterInterface, '$type' given",
                1538142318
            );
        }

        return $filterObject;
    }
}
