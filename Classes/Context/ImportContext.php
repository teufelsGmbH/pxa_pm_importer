<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Context;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Context\Context;
use Pixelant\PxaPmImporter\Exception\ContextDataAlreadyExistException;
use Pixelant\PxaPmImporter\Configuration\ConfigurationInterface;
use Pixelant\PxaPmImporter\Importer\ImporterInterface;
use Pixelant\PxaPmImporter\Source\SourceInterface;
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
    protected $importerName = null;

    /**
     * Importer instance
     *
     * @var ImporterInterface
     */
    protected $importer = null;

    /**
     * Keep name of current source
     *
     * @var string
     */
    protected $sourceName = null;

    /**
     * Source instance
     *
     * @var SourceInterface
     */
    protected $source = null;

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
     * Keep custom data
     *
     * @var array
     */
    protected $customData = [];

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->importStartTimeStamp = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');
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
    public function getImporterName(): ?string
    {
        return $this->importerName;
    }

    /**
     * @param string|null $importerName
     */
    public function setImporterName(?string $importerName): void
    {
        $this->importerName = $importerName;
    }

    /**
     * @return string|null
     */
    public function getSourceName(): ?string
    {
        return $this->sourceName;
    }

    /**
     * @param string|null $sourceName
     */
    public function setSourceName(?string $sourceName): void
    {
        $this->sourceName = $sourceName;
    }

    /**
     * @return array
     */
    public function getStoragePids(): ?array
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
    public function getNewRecordsPid(): ?int
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

    /**
     * @return ImporterInterface
     */
    public function getImporter(): ?ImporterInterface
    {
        return $this->importer;
    }

    /**
     * @param ImporterInterface $importer
     */
    public function setImporter(ImporterInterface $importer): void
    {
        $this->importer = $importer;
    }

    /**
     * @return SourceInterface
     */
    public function getSource(): ?SourceInterface
    {
        return $this->source;
    }

    /**
     * @param SourceInterface $source
     */
    public function setSource(SourceInterface $source): void
    {
        $this->source = $source;
    }

    /**
     * Set custom data
     *
     * @param string $key
     * @param $data
     * @param bool $allowOverride
     * @throws ContextDataAlreadyExistException
     */
    public function setData(string $key, $data, bool $allowOverride = true): void
    {
        if (array_key_exists($key, $this->customData) && !$allowOverride) {
            throw new ContextDataAlreadyExistException("Data already set for key '$key'", 1571830522234);
        }

        $this->customData[$key] = $data;
    }

    /**
     * Get from custom data
     *
     * @param string $key
     * @return mixed|null
     */
    public function getData(string $key)
    {
        return $this->customData[$key] ?? null;
    }

    /**
     * Set source and importer info about current import
     *
     * @param string $sourceName
     * @param SourceInterface $source
     * @param string $importerName
     * @param ImporterInterface $importer
     */
    public function setCurrentImportInfo(
        string $sourceName,
        SourceInterface $source,
        string $importerName,
        ImporterInterface $importer
    ): void {
        $this->sourceName = $sourceName;
        $this->source = $source;
        $this->importerName = $importerName;
        $this->importer = $importer;
    }

    /**
     * Reset info about current import
     */
    public function resetCurrentImportInfo(): void
    {
        $this->sourceName = null;
        $this->source = null;
        $this->importerName = null;
        $this->importer = null;
        $this->customData = [];
        $this->newRecordsPid = null;
        $this->storagePids = null;
    }
}
