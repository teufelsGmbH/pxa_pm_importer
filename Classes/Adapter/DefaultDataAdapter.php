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
     * @param array $row
     * @param int $languageUid
     * @return array
     */
    public function adaptRow(array $row, int $languageUid): array
    {
        if (!isset($this->languagesMapping[$languageUid])) {
            // @codingStandardsIgnoreStart
            throw new \UnexpectedValueException('Mapping missing for language "' . $languageUid . '" in data adapter', 1536051135215);
            // @codingStandardsIgnoreEnd
        }
        $mapping = $this->languagesMapping[$languageUid];
        if (is_array($this->identifier)) {
            $id = $this->getMultipleFieldData($this->identifier, $row);
        } else {
            $id = $this->getFieldData($this->identifier, $row);
        }

        $adaptedRow = [
            'id' => $id
        ];
        foreach ($mapping as $fieldName => $column) {
            $adaptedRow[$fieldName] = $this->getFieldData($column, $row);
        }

        return $adaptedRow;
    }
}
