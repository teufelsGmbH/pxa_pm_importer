<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Configuration;

use Pixelant\PxaPmImporter\Exception\InvalidConfigurationSourceException;
use Pixelant\PxaPmImporter\Exception\YamlResourceInvalidException;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @package Pixelant\PxaPmImporter\Configuration
 */
class YamlConfiguration extends AbstractConfiguration
{
    /**
     * Path to yaml configuration
     *
     * @var string
     */
    protected $yamlPath = '';

    /**
     * Initialize
     *
     * @param string $yamlPath Path to file
     */
    public function __construct(string $yamlPath)
    {
        $this->yamlPath = GeneralUtility::getFileAbsFileName($yamlPath);
    }

    /**
     *  Parse yaml configuration
     */
    protected function setConfigurationFromRawSource(): void
    {
        $configuration = Yaml::parseFile($this->yamlPath);

        if (!is_array($configuration)) {
            // @codingStandardsIgnoreStart
            throw new InvalidConfigurationSourceException('Yaml configuration is not array, but "' . gettype($configuration) . '"', 1535961126729);
            // @codingStandardsIgnoreEnd
        }

        if (isset($configuration['imports']) && is_array($configuration['imports'])) {
            foreach ($configuration['imports'] as $importYaml) {
                if (!empty($importYaml['resource'])) {
                    $importPath = dirname($this->yamlPath) . '/' . trim($importYaml['resource'], '/');

                    if ($this->isFileValid($importPath)) {
                        $configuration = array_merge_recursive($configuration, Yaml::parseFile($importPath));
                    } else {
                        // @codingStandardsIgnoreStart
                        throw new YamlResourceInvalidException('Invalid imports resource "' . $importYaml['resource'] . '"', 1537530881729);
                        // @codingStandardsIgnoreEnd
                    }
                }
            }
            unset($configuration['imports']);
        }

        $this->configuration = $configuration;
    }

    /**
     * Source
     *
     * @return string
     */
    protected function getConfigurationSource(): string
    {
        return $this->yamlPath;
    }
}
