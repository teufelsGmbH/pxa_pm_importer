<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Configuration;

use Pixelant\PxaPmImporter\Exception\InvalidConfigurationSourceException;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * @param string $yamlPath Absolute path to file
     */
    public function __construct(string $yamlPath)
    {
        $this->yamlPath = $yamlPath;
        parent::__construct();
    }

    /**
     * Check if yaml source is valid
     *
     * @return bool
     */
    public function isSourceValid(): bool
    {
        if (!empty($this->yamlPath)) {
            return file_exists($this->yamlPath) && is_readable($this->yamlPath);
        }

        return false;
    }

    /**
     *  Parse yaml configuration
     *
     * @return array
     */
    protected function parseConfiguration(): array
    {
        $configuration = Yaml::parse($this->readFileRawContent($this->yamlPath));

        if (!is_array($configuration)) {
            // @codingStandardsIgnoreStart
            throw new InvalidConfigurationSourceException('Parsed configuration is not array, but "' . gettype($configuration) . '"', 1535961126729);
            // @codingStandardsIgnoreEnd
        }
        return $configuration;
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
