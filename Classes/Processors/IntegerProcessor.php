<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

/**
 * Class IntegerProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class IntegerProcessor extends AbstractFieldProcessor
{
    /**
     * Set as int
     *
     * @param $value
     */
    public function process($value): void
    {
        $this->simplePropertySet(intval($value));
    }
}
