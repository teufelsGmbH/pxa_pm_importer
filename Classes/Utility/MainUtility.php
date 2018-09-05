<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

/**
 * Class MainUtility
 * @package Pixelant\PxaPmImporter\Utility
 */
class MainUtility
{
    /**
     * Convert db raw row to extbase model
     *
     * @param array $row
     * @param string $model
     * @return null|AbstractEntity
     */
    public static function convertRecordArrayToModel(array $row, string $model): AbstractEntity
    {
        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);

        $result = $dataMapper->map($model, [$row]);

        return $result[0];
    }

    /**
     * Get import id hash
     *
     * @param string $id
     * @return string
     */
    public static function getImportIdHash(string $id): string
    {
        return md5($id);
    }
}
