<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Status;

use Pixelant\PxaPmImporter\Domain\Model\DTO\ImportStatusInfo;
use Pixelant\PxaPmImporter\Domain\Model\Import;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ImportStatus
 * @package Pixelant\PxaPmImporter\Service\Status
 */
class ImportProgressStatus implements ImportProgressStatusInterface
{
    /**
     * @var Registry
     */
    protected $registry = null;

    /**
     * Registry namespace
     * @var string
     */
    protected $namespace = 'pxa_pm_importer_import_status';

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->registry = GeneralUtility::makeInstance(Registry::class);
    }

    /**
     * Start import
     *
     * @param Import $import
     */
    public function startImport(Import $import): void
    {
        $runningInfo = $this->getFromRegistry();
        $runningInfo[$import->getUid()] = GeneralUtility::makeInstance(ImportStatusInfo::class, $import)->toArray();

        $this->registrySet($runningInfo);
    }

    /**
     * End import
     *
     * @param Import $import
     */
    public function endImport(Import $import): void
    {
        $runningInfo = $this->getFromRegistry();
        if (isset($runningInfo[$import->getUid()])) {
            unset($runningInfo[$import->getUid()]);
            $this->registrySet($runningInfo);
        }
    }

    /**
     * Update import progress status
     *
     * @param Import $import
     * @param float $progress
     */
    public function updateImportProgress(Import $import, float $progress): void
    {
        $runningInfo = $this->getFromRegistry();
        if (isset($runningInfo[$import->getUid()])) {
            $runningInfo[$import->getUid()]['progress'] = $progress;
        }

        $this->registrySet($runningInfo);
    }

    /**
     * Return status of given import
     *
     * @param Import $import
     * @return ImportStatusInfo
     */
    public function getImportStatus(Import $import): ImportStatusInfo
    {
        $runningInfo = $this->getFromRegistry();
        if (isset($runningInfo[$import->getUid()])) {
            $importInfo = $runningInfo[$import->getUid()];

            return GeneralUtility::makeInstance(
                ImportStatusInfo::class,
                $import,
                $importInfo['start'],
                $importInfo['progress']
            );
        }

        return GeneralUtility::makeInstance(ImportStatusInfo::class, $import)->setIsAvailable(false);
    }

    /**
     * Write to registry
     *
     * @param array $data
     */
    protected function registrySet(array $data): void
    {
        $this->registry->set($this->namespace, 'runningImports', $data);
    }

    /**
     * Read from registry
     *
     * @return array
     */
    protected function getFromRegistry(): array
    {
        return $this->registry->get($this->namespace, 'runningImports', []);
    }
}
