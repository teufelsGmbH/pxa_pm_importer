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
     * Initialize configuration
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Getter for configuration, return full array
     *
     * @return array
     */
    public function getConfiguration(): array
    {
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
     * Initialize main method
     */
    protected function initialize(): void
    {
        if ($this->isSourceValid()) {
            $configuration = $this->parseConfiguration();
            $this->emitSignal(
                __CLASS__,
                'postConfigurationParse',
                ['configuration' => &$configuration]
            );

            $this->configuration = $configuration;
        } else {
            // @codingStandardsIgnoreStart
            throw new InvalidConfigurationSourceException('Configuration source "' . $this->getConfigurationSource() . '" is invalid', 1535959642938);
            // @codingStandardsIgnoreEnd
        }
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
     * @return array
     */
    abstract protected function parseConfiguration(): array;

    /**
     * Return configuration source
     *
     * @return string
     */
    abstract protected function getConfigurationSource(): string;
}
