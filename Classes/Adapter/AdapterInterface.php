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
     * Initialize adapter
     *
     * @param array $configuration
     */
    public function initialize(array $configuration): void;

    /**
     * Adapt single row
     * Expect to return array with field name to value
     *
     * ['name' => 'product 1', 'sku' => 123]
     *
     * @param array $row Row from source
     * @param int $languageUid Current import language
     * @return array
     */
    public function adaptRow(array $row, int $languageUid): array;

    /**
     * Check if row should be included in import
     *
     * @param array $row
     * @return bool
     */
    public function includeRow(array $row): bool;

    /**
     * Array with UIDs of import languages
     * Importer will iterate through each language
     * and run import source data with language uid
     *
     * @return array
     */
    public function getImportLanguages(): array;
}
