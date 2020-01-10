<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Utility;

/**
 * Class MainUtility
 * @package Pixelant\PxaPmImporter\Utility
 */
class MainUtility
{
    /**
     * Convert A to 0, B to 1 and so on
     *
     * @param string $column
     * @return int
     */
    public static function convertAlphabetColumnToNumber(string $column): int
    {
        /// @codingStandardsIgnoreStart
        $alphabet = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        // @codingStandardsIgnoreEnd

        $column = trim($column);

        if (empty($column)) {
            throw new \UnexpectedValueException('Column value could not be empty', 1536221838124);
        }
        $length = strlen($column);
        if ($length > 2) {
            throw new \LengthException('Maximum column value can be 2 chars', 1536221841673);
        }

        if ($length === 1) {
            return array_search(strtoupper($column), $alphabet);
        } else {
            $firstValue = (array_search(strtoupper($column[0]), $alphabet) + 1) * count($alphabet);
            return $firstValue + array_search(strtoupper($column[1]), $alphabet);
        }
    }

    /**
     * Get memory usage
     *
     * @return string
     */
    public static function getMemoryUsage(): string
    {
        $size = memory_get_usage(true);
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];

        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[(int)$i];
    }
}
