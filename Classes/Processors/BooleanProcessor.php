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
     * Boolean
     *
     * @param $value
     * @return bool
     */
    public function postProcess($value): bool
    {
        return (bool)$value;
    }
}
