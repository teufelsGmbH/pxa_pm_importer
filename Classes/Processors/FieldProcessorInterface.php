<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Interface FieldProcessorInterface
 * @package Pixelant\PxaPmImporter\Processors
 */
interface FieldProcessorInterface
{
    /**
     * Process single import field
     * This method is called to set value in model property and do any other required operations
     *
     * @param $value
     */
    public function process($value): void;
}
