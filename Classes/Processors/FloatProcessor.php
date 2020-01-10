<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

/**
 * Class FloatProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class FloatProcessor extends AbstractFieldProcessor implements PreProcessorInterface
{
    /**
     * Remove comma
     *
     * @param mixed $value
     * @return float
     */
    public function preProcess($value)
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }

        return (float)$value;
    }
}
