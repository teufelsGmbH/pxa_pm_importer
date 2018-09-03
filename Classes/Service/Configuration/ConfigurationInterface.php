<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Configuration;

/**
 * Interface ConfigurationReaderInterface
 * @package Pixelant\PxaPmImporter\Service
 */
interface ConfigurationInterface
{
    /**
     * @return bool
     */
    public function isSourceValid(): bool;

    /**
     * Return full configuration
     *
     * @return array
     */
    public function getConfiguration(): array;

    /**
     * Get configuration of source
     *
     * @return array
     */
    public function getSourceConfiguration(): array;

    /**
     * Get configuration of source
     *
     * @return array
     */
    public function getImportersConfiguration(): array;
}
