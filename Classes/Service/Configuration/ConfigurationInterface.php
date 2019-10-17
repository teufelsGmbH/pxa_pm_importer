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
    public function getSourcesConfiguration(): array;

    /**
     * Get configuration of importer
     *
     * @return array
     */
    public function getImportersConfiguration(): array;

    /**
     * Return custom log path
     *
     * @return string|null
     */
    public function getLogPath(): ?string;
}
