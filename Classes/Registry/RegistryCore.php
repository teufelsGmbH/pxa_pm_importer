<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Registry;

use TYPO3\CMS\Core\Registry;

/**
 * Extend TYPO3 registry with additional functions
 * @package Pixelant\PxaPmImporter\Registry
 */
class RegistryCore extends Registry
{
    /**
     * Get entries by namespace
     *
     * @param string $namespace
     * @param $defaultValue
     * @return array
     */
    public function getByNamespace(string $namespace, $defaultValue = null): array
    {
        $this->validateNamespace($namespace);
        if (!$this->isNamespaceLoaded($namespace)) {
            $this->loadEntriesByNamespace($namespace);
        }

        return $this->entries[$namespace] ?? $defaultValue;
    }
}