<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

/**
 * Class BooleanProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class BooleanProcessor extends AbstractFieldProcessor implements PreProcessorInterface
{
    /**
     * @inheritDoc
     */
    public function preProcess($value)
    {
        return (bool)$value;
    }
}
