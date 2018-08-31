<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Utility;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ImportersRegistry
 * @package Pixelant\PxaPmImporter\Utility
 */
class ImportersRegistry
{
    /**
     * Registry of all importers
     *
     * @var array
     */
    private static $importers = [];

    /**
     * Register new importer
     *
     * @param string $extKey Extension key
     * @param array $paths Array of folders inside EXT:ext_key/Configuration where to fetch configuration files
     */
    public static function registerImporter(string $extKey, array $paths = null): void
    {
        if (!array_key_exists($extKey, self::$importers)) {
            if ($paths === null) {
                $paths = ['Yaml'];
            }
            self::$importers[$extKey] = $paths;
        }
    }

    /**
     * Return registered importers
     *
     * @return array
     */
    public static function getRegisterImporters(): array
    {
        return self::$importers;
    }

    /**
     * Collect all available configurations for importers
     *
     * @return array
     */
    public static function getImportersAvailableConfigurations(): array
    {
        static $availableConfigurations;
        if ($availableConfigurations !== null) {
            return $availableConfigurations;
        }

        $availableConfigurations = [];
        foreach (self::getRegisterImporters() as $importer => $folders) {
            if (!ExtensionManagementUtility::isLoaded($importer)) {
                continue;
            }

            $configurationFiles = [];
            foreach ($folders as $folder) {
                $extensionPath = self::getConfigurationFolderExtensionPath($importer, $folder);
                $fullPath = GeneralUtility::getFileAbsFileName($extensionPath);
                if (is_dir($fullPath)) {
                    $files = GeneralUtility::getFilesInDir($fullPath, 'yaml');
                    foreach ($files as $file) {
                        $configurationFiles[] = $extensionPath . $file;
                    }
                }
            }

            $availableConfigurations[$importer] = $configurationFiles;
        }

        return $availableConfigurations;
    }

    /**
     * Get importer configuration folder path
     *
     * @param string $extKey
     * @param string $folder
     * @return string
     */
    protected static function getConfigurationFolderExtensionPath(string $extKey, string $folder): string
    {
        return sprintf(
            'EXT:%s/Configuration/%s/',
            $extKey,
            trim($folder, '/')
        );
    }
}
