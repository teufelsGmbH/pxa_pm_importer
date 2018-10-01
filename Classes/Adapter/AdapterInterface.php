<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Adapter;

/**
 * Interface AdapterInterface
 * @package Pixelant\PxaPmImporter\Adapter
 */
interface AdapterInterface
{
    /**
     * Adapt raw data from source
     *
     * @param array $data Raw data from source
     * @param array $configuration Adapter configuration
     * @return array
     */
    public function adapt(array $data, array $configuration): void;

    /**
     * Get full adapted data for all languages
     *
     * @return array
     */
    public function getData(): array;

    /**
     * Language layer data
     * [
     *  'fieldName' => 'value',
     *  'field2' => 'value2'
     * ]
     *
     * @param int $languageUid
     * @return array
     */
    public function getLanguageData(int $languageUid): array;

    /**
     * Array with UIDs of languages
     *
     * @return array
     */
    public function getLanguages(): array;
}
