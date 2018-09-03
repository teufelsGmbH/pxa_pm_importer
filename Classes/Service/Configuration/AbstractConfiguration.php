<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service;

use Pixelant\PxaPmImporter\Exception\InvalidConfigurationSourceException;
use Pixelant\PxaPmImporter\Traits\EmitSignalTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * Initialize main method
     */
    protected function initialize(): void
    {
        if ($this->isSourceValid()) {
            $configuration = $this->parseConfiguration();
            $this->emitSignal('postConfigurationParse', [&$configuration]);

            $this->configuration = $configuration;
        }

        // @codingStandardsIgnoreStart
        throw new InvalidConfigurationSourceException('Configuration source "' . $this->getConfigurationSource() . '" is invalid', 1535959642938);
        // @codingStandardsIgnoreEnd
    }

    /**
     * Read content from file
     *
     * @param string $filePath
     * @return string
     */
    protected function readFileRawContent(string $filePath): string
    {
        $filePath = GeneralUtility::getFileAbsFileName($filePath);

        $content = @file_get_contents($filePath);

        return $content ?: '';
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
