<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Context;

use Pixelant\PxaPmImporter\Service\Configuration\ConfigurationInterface;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Import context information
 *
 * @package Pixelant\PxaPmImporter\Context
 */
class ImportContext implements SingletonInterface
{
    /**
     * Path to import configuration(YAML file)
     *
     * @var string
     */
    protected $importConfigurationSource = null;

    /**
     * Import configuration provider. Read configuration from file
     *
     * @var ConfigurationInterface
     */
    protected $configurationService = null;

    /**
     * Import start timestamp
     *
     * @var int
     */
    protected $importStartTimeStamp = null;

    /**
     * Keep name of current importer
     *
     * @var string
     */
    protected $currentImporter = null;

    /**
     * Keep name of current source
     *
     * @var string
     */
    protected $currentSource = null;

    /**
     * Importer storage, where to fetch records
     *
     * @var array
     */
    protected $storagePids = null;

    /**
     * New records pid
     *
     * @var int
     */
    protected $newRecordsPid = null;

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->importStartTimeStamp = $GLOBALS['EXEC_TIME'];
    }

    /**
     * @return string
     */
    public function getImportConfigurationSource(): string
    {
        return $this->importConfigurationSource;
    }

    /**
     * @param string $importConfigurationSource
     */
    public function setImportConfigurationSource(string $importConfigurationSource): void
    {
        $this->importConfigurationSource = $importConfigurationSource;
    }

    /**
     * @return ConfigurationInterface
     */
    public function getConfigurationService(): ConfigurationInterface
    {
        return $this->configurationService;
    }

    /**
     * @param ConfigurationInterface $configurationService
     */
    public function setConfigurationService(ConfigurationInterface $configurationService): void
    {
        $this->configurationService = $configurationService;
    }

    /**
     * Return timestamp when import started
     *
     * @return int
     */
    public function getImportStartTimeStamp(): int
    {
        return $this->importStartTimeStamp;
    }

    /**
     * @return string|null
     */
    public function getCurrentImporter(): ?string
    {
        return $this->currentImporter;
    }

    /**
     * @param string|null $currentImporter
     */
    public function setCurrentImporter(?string $currentImporter): void
    {
        $this->currentImporter = $currentImporter;
    }

    /**
     * @return string|null
     */
    public function getCurrentSource(): ?string
    {
        return $this->currentSource;
    }

    /**
     * @param string|null $currentSource
     */
    public function setCurrentSource(?string $currentSource): void
    {
        $this->currentSource = $currentSource;
    }

    /**
     * @return array
     */
    public function getStoragePids(): array
    {
        return $this->storagePids;
    }

    /**
     * @param array $storagePids
     */
    public function setStoragePids(array $storagePids): void
    {
        $this->storagePids = $storagePids;
    }

    /**
     * @return int
     */
    public function getNewRecordsPid(): int
    {
        return $this->newRecordsPid;
    }

    /**
     * @param int $newRecordsPid
     */
    public function setNewRecordsPid(int $newRecordsPid): void
    {
        $this->newRecordsPid = $newRecordsPid;
    }
}
