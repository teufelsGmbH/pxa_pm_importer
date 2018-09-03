<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service;

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
}
