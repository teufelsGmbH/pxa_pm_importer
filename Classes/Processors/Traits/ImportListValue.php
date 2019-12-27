<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Traits;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Trait ImportValueArray
 * @package Pixelant\PxaPmImporter\Processors\Traits
 */
trait ImportListValue
{
    /**
     * Convert comma-list import value to array or return original array
     *
     * @param array|string $list
     * @param string $delim
     * @return array
     */
    protected function convertListToArray($list, string $delim = ','): array
    {
        if (!is_array($list) && !is_string($list)) {
            $type = gettype($list);
            throw new \InvalidArgumentException(
                "Expected to get array or string as import list. '{$type}' given.",
                1560319588819
            );
        }
        if (is_string($list)) {
            $list = GeneralUtility::trimExplode($delim, $list, true);
        }

        return $list;
    }
}
