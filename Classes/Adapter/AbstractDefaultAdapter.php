<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Adapter;

use Pixelant\PxaPmImporter\Exception\InvalidAdapterFieldMapping;
use Pixelant\PxaPmImporter\Adapter\Filters\FilterInterface;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AbstractDefaultAdapter
 * @package Pixelant\PxaPmImporter\Adapter
 */
abstract class AbstractDefaultAdapter implements AdapterInterface
{
    /**
     * Adapted data for all lanugages
     *
     * @var array
     */
    protected $data = [];

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
     * Adapt source data
     *
     * @param array $data
     * @param array $configuration
     */
    public function adapt(array $data, array $configuration): void
    {
        $this->initialize($configuration);
        $this->data = $this->adaptData(
            $this->transformSourceData($data)
        );
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getLanguages(): array
    {
        return array_keys($this->languagesMapping);
    }

    /**
     * @param int $languageUid
     * @return array
     */
    public function getLanguageData(int $languageUid): array
    {
        if (isset($this->data[$languageUid])) {
            return $this->data[$languageUid];
        }

        // @codingStandardsIgnoreStart
        throw new \UnexpectedValueException('Language with uid "' . $languageUid . '" doesn\'t have data in data adapter', 1536051135215);
        // @codingStandardsIgnoreEnd
    }

    /**
     * Initialize default settings
     *
     * @param array $configuration
     */
    protected function initialize(array $configuration): void
    {
        if (empty($configuration['mapping'])) {
            throw new \RuntimeException('Adapter mapping configuration is invalid.', 1536050678725);
        }
        $isExcelColumns = isset($configuration['mapping']['excelColumns'])
            ? (bool)$configuration['mapping']['excelColumns']
            : false;

        if (isset($configuration['mapping']['id'])) {
            if (is_numeric($configuration['mapping']['id'])) {
                $this->identifier = (int)$configuration['mapping']['id'];
            } elseif (is_string($configuration['mapping']['id'])) {
                if ($isExcelColumns) {
                    $this->identifier = MainUtility::convertAlphabetColumnToNumber($configuration['mapping']['id']);
                } else {
                    $this->identifier = $configuration['mapping']['id'];
                }
            } elseif (is_array($configuration['mapping']['id'])) {
                if (count($configuration['mapping']['id']) < 1) {
                    // @codingStandardsIgnoreStart
                    throw new \UnexpectedValueException('Adapter "id" (identifier) as array should have at least one element.', 1538560400221);
                    // @codingStandardsIgnoreEnd
                }

                $this->identifier = $configuration['mapping']['id'];
            }

            if ($this->identifier === null) {
                // @codingStandardsIgnoreStart
                throw new \RuntimeException('Could not set adapter "id" (identifier). String, numeric and array values are only supported.', 1538560523613);
                // @codingStandardsIgnoreEnd
            }
        } else {
            throw new \RuntimeException('Adapter mapping require "id" (identifier) mapping to be set.', 1536050717594);
        }

        if (!empty($configuration['mapping']['languages']) && is_array($configuration['mapping']['languages'])) {
            $this->languagesMapping = $configuration['mapping']['languages'];

            if ($isExcelColumns) {
                foreach ($this->languagesMapping as $language => $languageMapping) {
                    foreach ($languageMapping as $field => $column) {
                        if (!is_numeric($column)) {
                            $columnNumber = MainUtility::convertAlphabetColumnToNumber($column);
                            $this->languagesMapping[$language][$field] = $columnNumber;
                        }
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
     * Get single field data from row
     *
     * @param $column
     * @param array $row
     * @return mixed
     */
    protected function getFieldData($column, array $row)
    {
        if (array_key_exists($column, $row)) {
            return $row[$column];
        }

        throw new InvalidAdapterFieldMapping('Data column "' . $column . '" is not set', 1536051927592);
    }

    /**
     * Get multiple field data from row
     *
     * @param array $columns
     * @param array $row
     * @return mixed
     */
    protected function getMultipleFieldData(array $columns, array $row): string
    {
        $fieldData = '';

        foreach ($columns as $column) {
            $fieldData .= $this->getFieldData($column, $row);
        }

        return $fieldData;
    }

    /**
     * Convert source data according to mapping
     *
     * @param array $data
     * @return array
     */
    protected function adaptData(array $data): array
    {
        $adaptData = [];
        // Prepare arrays with languages
        foreach (array_keys($this->languagesMapping) as $languageUid) {
            $adaptData[$languageUid] = [];
        }

        foreach ($data as $dataRow) {
            if (is_array($this->identifier)) {
                $id = $this->getMultipleFieldData($this->identifier, $dataRow);
            } else {
                $id = $this->getFieldData($this->identifier, $dataRow);
            }

            if ($this->includeRow($dataRow)) {
                foreach ($this->languagesMapping as $language => $mapping) {
                    $languageDataRow = [
                        'id' => $id
                    ];

                    foreach ($mapping as $fieldName => $column) {
                        $languageDataRow[$fieldName] = $this->getFieldData($column, $dataRow);
                    }

                    $adaptData[$language][] = $languageDataRow;
                }
            }
        }

        return $adaptData;
    }

    /**
     * Check if row should be excluded a filter
     *
     * @param array $dataRow
     * @return boolean
     */
    protected function includeRow(array $dataRow): bool
    {
        if (is_array($this->filters) && count($this->filters) > 0) {
            foreach ($this->filters as $column => $filter) {
                if (!empty($filter['filter'])) {
                    $filterObject = GeneralUtility::makeInstance($filter['filter']);
                    if (!($filterObject instanceof FilterInterface)) {
                        // @codingStandardsIgnoreStart
                        throw new \UnexpectedValueException('Filter "' . $filter['filter'] . '" should be instance of "FilterInterface"', 1538142318);
                        // @codingStandardsIgnoreEnd
                    }
                    if (!$filterObject->includeRow($column, $dataRow, $filter)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Do final source raw data processing before adapting
     *
     * @param array $data
     * @return array
     */
    abstract protected function transformSourceData(array $data): array;
}
