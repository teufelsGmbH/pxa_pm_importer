<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Utility;

/**
 * @package Pixelant\PxaPmImporter\Utility
 */
class HashUtility
{
    /**
     * Get import id hash
     *
     * @param string $id
     * @return string
     */
    public static function hashImportId(string $id): string
    {
        return md5($id);
    }
}
