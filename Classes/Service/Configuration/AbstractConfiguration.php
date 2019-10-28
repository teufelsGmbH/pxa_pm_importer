<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Configuration;

use Pixelant\PxaPmImporter\Exception\InvalidConfigurationSourceException;
use Pixelant\PxaPmImporter\Traits\EmitSignalTrait;

/**
 * Class AbstractConfiguration
 * @package Pixelant\PxaPmImporter\Service
 */
abstract class AbstractConfiguration implements ConfigurationInterface
{
    use EmitSignalTrait;

    /**
     * Configuration from source
     *
     * @var array
     */
    protected $configuration = null;

    /**
     * Getter for configuration, return full array
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        if ($this->configuration === null) {
            $this->setConfigurationFromRawSource();
        }

        return $this->configuration;
    }

    /**
     * Sources configuration
     *
     * @return array
     */
    public function getSourcesConfiguration(): array
    {
        $configuration = $this->getConfiguration();

        if (!isset($configuration['sources']) || !is_array($configuration['sources'])) {
            throw new \UnexpectedValueException('Missing "sources" configuration.', 1538134061217);
        }
        return $configuration['sources'];
    }

    /**
     * Importer configuration
     *
     * @return array
     */
    public function getImportersConfiguration(): array
    {
        $configuration = $this->getConfiguration();

        if (!isset($configuration['importers']) || !is_array($configuration['importers'])) {
            throw new \UnexpectedValueException('Missing "importers" configuration.', 1538134039200);
        }
        return $configuration['importers'];
    }

    /**
     * Read custom log path from settings
     *
     * @return string|null
     */
    public function getLogPath(): ?string
    {
        $configuration = $this->getConfiguration();
        if (!empty($configuration['log']['path'])) {
            return $configuration['log']['path'];
        }

        return null;
    }

    /**
     * Read log severity
     *
     * @return int|null
     */
    public function getLogSeverity(): ?int
    {
        $configuration = $this->getConfiguration();
        if (!empty($configuration['log']['severity'])) {
            return (int)$configuration['log']['severity'];
        }

        return null;
    }

    /**
     * Check if file path is valid
     *
     * @param string $filePath
     * @return bool
     */
    protected function isFileValid(string $filePath): bool
    {
        return file_exists($filePath) && is_readable($filePath);
    }

    /**
     * Parse configuration source as array
     */
    abstract protected function setConfigurationFromRawSource(): void;

    /**
     * Return configuration source. For example file path or API url
     *
     * @return string
     */
    abstract protected function getConfigurationSource(): string;
}
