<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

/**
 * Class IntegerProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class IntegerProcessor extends AbstractFieldProcessor implements PreProcessorInterface
{
    /**
     * @param mixed $value
     * @return int
     */
    public function preProcess($value)
    {
        return (int)$value;
    }
}
