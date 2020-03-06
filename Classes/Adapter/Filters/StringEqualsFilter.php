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
     * Check if row should be included
     *
     * @param mixed $column Column identifier
     * @param mixed $key Row data key
     * @param array $rawData Raw data from source
     * @param int $languageUid Language uid of current import row
     * @param array $configuration Filter configuration
     * @return bool
     */
    public function includeRow($column, $key, $rawData, int $languageUid, array $configuration): bool
    {
        if (!is_array($rawData)) {
            throw new \InvalidArgumentException('StringEqualsFilter accept only array as raw data', 1571899495228);
        }
        if (!isset($configuration['value'])) {
            throw new InvalidAdapterFilterValue('Filter value for column "' . $column . '" is not set', 1537873098);
        }

        if (array_key_exists($column, $rawData)) {
            return strcasecmp(trim($rawData[$column]), trim($configuration['value'])) === 0;
        } else {
            throw new InvalidAdapterFilterColumn('Data column "' . $column . '" is not set', 1537866865);
        }
    }
}
