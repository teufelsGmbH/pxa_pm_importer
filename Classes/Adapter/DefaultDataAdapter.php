<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Adapter;

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
     * @throws \Pixelant\PxaPmImporter\Exception\InvalidAdapterFieldMapping
     */
    public function adaptRow($key, array $row, int $languageUid): array
    {
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
}
