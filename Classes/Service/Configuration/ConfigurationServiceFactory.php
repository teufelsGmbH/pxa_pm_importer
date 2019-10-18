<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Configuration;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Class ConfigurationProvider
 * @package Pixelant\PxaPmImporter\Service\Configuration
 */
class ConfigurationServiceFactory
{
    /**
     * Factory for configuration
     *
     * @param string $configuration
     * @return ConfigurationInterface
     */
    public function createConfiguration(string $configuration): ConfigurationInterface
    {
        if (StringUtility::endsWith($configuration, '.yaml')) {
            return GeneralUtility::makeInstance(YamlConfiguration::class, $configuration);
        }

        throw new \InvalidArgumentException(
            "Only yaml configuration is supported so far, '$configuration'' has not '.yaml' extension",
            1571303848102
        );
    }
}
