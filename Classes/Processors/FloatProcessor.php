<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

/**
 * Class FloatProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class FloatProcessor extends AbstractFieldProcessor
{
    /**
     * Remove comma
     *
     * @param mixed $value
     */
    public function preProcess(&$value): void
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }
    }

    /**
     * Set as float
     *
     * @param $value
     */
    public function process($value): void
    {
        $this->simplePropertySet((float)$value);
    }
}
