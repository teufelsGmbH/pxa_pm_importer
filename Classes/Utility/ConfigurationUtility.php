<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ConfigurationUtility
 * @package Pixelant\PxaPmImporter\Utility
 */
class ConfigurationUtility
{
    /**
     * @var array
     */
    protected static $extensionManagerConfiguration = null;

    /**
     * Get extension manager settings
     *
     * @return array
     */
    public static function getExtMgrConfiguration(): array
    {
        if (self::$extensionManagerConfiguration === null) {
            if (class_exists('TYPO3\\CMS\\Core\\Configuration\\ExtensionConfiguration')) {
                $extensionConfiguration = GeneralUtility::makeInstance(
                    'TYPO3\\CMS\\Core\\Configuration\\ExtensionConfiguration'
                )->get('pxa_pm_importer');
            } else {
                $extensionConfiguration = unserialize(
                    $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['pxa_pm_importer'] ?? ''
                );
            }

            self::$extensionManagerConfiguration = GeneralUtility::removeDotsFromTS($extensionConfiguration ?: []);
        }

        return self::$extensionManagerConfiguration;
    }
}
