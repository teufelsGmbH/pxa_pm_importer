<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Adapter\Filters;

use Pixelant\PxaPmImporter\Exception\InvalidAdapterFilterColumn;
use Pixelant\PxaPmImporter\Exception\InvalidAdapterFilterValue;

/**
 * StringEqualsFilter
 * @package Pixelant\PxaPmImporter\Adapter\Filters
 */
class StringEqualsFilter implements FilterInterface
{
    /**
     * Adapt raw data from source
     *
     * @param $column Column identifier
     * @param array $rowData Raw data from source
     * @param array $configuration Filter configuration
     * @return bool
     */
    public function includeRow($column, array $rowData, array $configuration): bool
    {
        if (!isset($configuration['value'])) {
            throw new InvalidAdapterFilterValue('Filter value for column "' . $column . '" is not set', 1537873098);
        }

        if (array_key_exists($column, $rowData)) {
            return strcasecmp(trim($rowData[$column]), trim($configuration['value'])) === 0;
        } else {
            throw new InvalidAdapterFilterColumn('Data column "' . $column . '" is not set', 1537866865);
        }
    }
}
