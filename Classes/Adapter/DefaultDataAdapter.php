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
     * Convert source data
     *
     * @param array $data
     * @return array
     */
    protected function adaptSourceData(array $data): array
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
}
