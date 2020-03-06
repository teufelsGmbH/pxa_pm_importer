<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

/**
 * @package Pixelant\PxaPmImporter\Domain\Repository
 */
class FileReferenceRepository
{
    /**
     * Set deleted flag for given file reference
     *
     * @param FileReference $fileReference
     */
    public function remove(FileReference $fileReference)
    {
        $deletedField = $GLOBALS['TCA']['sys_file_reference']['ctrl']['delete'];
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_file_reference')
            ->update(
                'sys_file_reference',
                [$deletedField => 1],
                ['uid' => $fileReference->getUid()]
            );
    }
}
