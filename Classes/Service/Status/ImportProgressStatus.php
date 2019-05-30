<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Status;

use Pixelant\PxaPmImporter\Domain\Model\DTO\ImportStatusInfo;
use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Domain\Repository\ImportRepository;
use Pixelant\PxaPmImporter\Registry\RegistryCore;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ImportStatus
 * @package Pixelant\PxaPmImporter\Service\Status
 */
class ImportProgressStatus implements ImportProgressStatusInterface
{
    /**
     * @var RegistryCore
     */
    protected $registry = null;

    /**
     * @var ImportRepository
     */
    protected $importRepository = null;


    /**
     * Registry namespace
     *
     * @var string
     */
    protected $namespace = 'pxa_pm_importer_import_status';

    /**
     * Prefix of import registry entry
     *
     * @var string
     */
    protected $importRegistryKey = 'running_';

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->registry = GeneralUtility::makeInstance(RegistryCore::class);
        $this->importRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(ImportRepository::class);
    }

    /**
     * Start import
     *
     * @param Import $import
     */
    public function startImport(Import $import): void
    {
        $importInfo = GeneralUtility::makeInstance(ImportStatusInfo::class, $import)->toArray();

        $this->registrySet($import, $importInfo);
    }

    /**
     * End import
     *
     * @param Import $import
     */
    public function endImport(Import $import): void
    {
        $this->registry->remove(
            $this->namespace,
            $this->getImportRegistryKey($import)
        );
    }

    /**
     * Update import progress status
     *
     * @param Import $import
     * @param float $progress
     */
    public function updateImportProgress(Import $import, float $progress): void
    {
        $importInfo = $this->getFromRegistry($import);
        if ($importInfo !== null) {
            $importInfo['progress'] = $progress;
        }

        $this->registrySet($import, $importInfo);
    }

    /**
     * Return status of given import
     *
     * @param Import $import
     * @return ImportStatusInfo
     */
    public function getImportStatus(Import $import): ImportStatusInfo
    {
        $importInfo = $this->getFromRegistry($import);
        if ($importInfo !== null) {
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
     * Get all imports info
     *
     * @return array
     */
    public function getAllRunningImports(): array
    {
        $runningInfo = $this->registry->getByNamespace($this->namespace, []);

        $result = [];
        foreach ($runningInfo as $importInfo) {
            $import = $this->importRepository->findByUid((int)$importInfo['import']);
            if ($import === null) {
                continue;
            }

            $result[] = GeneralUtility::makeInstance(
                ImportStatusInfo::class,
                $import,
                $importInfo['start'],
                $importInfo['progress']
            );
        }

        return $result;
    }

    /**
     * Write to registry
     *
     * @param Import $import
     * @param array $data
     */
    protected function registrySet(Import $import, array $data): void
    {
        $this->registry->set($this->namespace, $this->getImportRegistryKey($import), $data);
    }

    /**
     * Read from registry
     *
     * @param Import $import
     * @return array
     */
    protected function getFromRegistry(Import $import): ?array
    {
        return $this->registry->get($this->namespace, $this->getImportRegistryKey($import));
    }

    /**
     * Generate key for import registry
     *
     * @param Import $import
     * @return string
     */
    protected function getImportRegistryKey(Import $import): string
    {
        return $this->importRegistryKey . $import->getUid();
    }
}
