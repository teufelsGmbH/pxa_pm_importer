<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Adapter\Filters;

/**
 * Interface AdapterInterface
 * @package Pixelant\PxaPmImporter\Adapter\Filters
 */
interface FilterInterface
{
    /**
     * Adapt raw data from source
     *
     * @param $column Column identifier
     * @param array $rowData Raw data from source
     * @param array $configuration Filter configuration
     * @return bool
     */
    public function includeRow($column, array $rowData, array $configuration): bool;
}
