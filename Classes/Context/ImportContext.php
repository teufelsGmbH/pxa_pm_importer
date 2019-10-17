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
}
