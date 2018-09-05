<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

/**
 * Class BooleanProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class BooleanProcessor extends AbstractFieldProcessor
{
    /**
     * Set boolean value
     *
     * @param $value
     */
    public function process($value): void
    {
        $this->simplePropertySet((bool)$value);
    }
}
