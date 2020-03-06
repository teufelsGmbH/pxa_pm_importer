<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Adapter;

use Pixelant\PxaPmImporter\Exception\InvalidAdapterFieldMapping;

/**
 * Class DefaultDataAdapter
 * @package Pixelant\PxaPmImporter\Adapter
 */
class DefaultDataAdapter extends AbstractDefaultAdapter
{
    /**
     * Convert source data according to mapping
     *
     * @param mixed $key
     * @param array $row
     * @param int $languageUid
     * @return array
     * @throws InvalidAdapterFieldMapping
     */
    public function adaptRow($key, $row, int $languageUid): array
    {
        if (!is_array($row)) {
            $type = gettype($row);
            throw new \InvalidArgumentException("Expect row to be an array '$type' given", 1571899623505);
        }

        if (!isset($this->languagesMapping[$languageUid])) {
            // @codingStandardsIgnoreStart
            throw new \UnexpectedValueException('Mapping missing for language "' . $languageUid . '" in data adapter', 1536051135215);
            // @codingStandardsIgnoreEnd
        }

        $adaptedRow = [
            'id' => $this->getFieldData($this->identifier, $row)
        ];

        $mapping = $this->languagesMapping[$languageUid];
        foreach ($mapping as $fieldName => $column) {
            $adaptedRow[$fieldName] = $this->getFieldData($column, $row);
        }

        return $adaptedRow;
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
        if (is_array($column)) {
            return $this->getMultipleFieldData($column, $row);
        }

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
}
