<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Adapter;

use Pixelant\PxaPmImporter\Exception\InvalidAdapterFieldMapping;
use Pixelant\PxaPmImporter\Utility\MainUtility;

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
     * @var int
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
    protected $configuration = [];

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
            } elseif ($isExcelColumns) {
                $this->identifier = MainUtility::convertAlphabetColumnToNumber($configuration['mapping']['id']);
            } else {
                $this->identifier = $configuration['mapping']['id'];
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

        // Save configuration
        $this->configuration = $configuration;
    }

    /**
     * Get single field data from row
     *
     * @param int $column
     * @param array $row
     * @return mixed
     */
    protected function getFieldData(int $column, array $row)
    {
        if (array_key_exists($column, $row)) {
            return $row[$column];
        }

        throw new InvalidAdapterFieldMapping('Data column "' . $column . '" is not set', 1536051927592);
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
            $id = $this->getFieldData($this->identifier, $dataRow);
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

        return $adaptData;
    }

    /**
     * Do final source raw data processing before adapting
     *
     * @param array $data
     * @return array
     */
    abstract protected function transformSourceData(array $data): array;
}
