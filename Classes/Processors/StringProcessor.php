<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

/**
 * Class StringProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class StringProcessor extends AbstractFieldProcessor implements PreProcessorInterface
{
    /**
     * @inheritDoc
     */
    public function preProcess($value)
    {
        return (string)$value;
    }
}
