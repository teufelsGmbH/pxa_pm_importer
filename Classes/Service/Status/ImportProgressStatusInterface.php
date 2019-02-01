<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Status;

use Pixelant\PxaPmImporter\Domain\Model\DTO\ImportStatusInfo;
use Pixelant\PxaPmImporter\Domain\Model\Import;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class ImportStatusInterface
 * @package Pixelant\PxaPmImporter\Service\Status
 */
interface ImportProgressStatusInterface extends SingletonInterface
{
    /**
     * Return status of import
     *
     * @param Import $import
     * @return ImportStatusInfo
     */
    public function getImportStatus(Import $import): ImportStatusInfo;

    /**
     * Update import progress status
     *
     * @param Import $import
     * @param int $progress
     * @return mixed
     */
    public function updateImportProgress(Import $import, int $progress): void;

    /**
     * Mark import as the one that has started
     *
     * @param Import $import
     */
    public function startImport(Import $import): void;

    /**
     * Mark import as the one that has ended
     *
     * @param Import $import
     */
    public function endImport(Import $import): void;
}
