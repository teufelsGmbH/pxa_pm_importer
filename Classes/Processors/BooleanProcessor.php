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
     * @param $value
     * @return bool
     */
    public function isValid($value): bool
    {
        return true; // @TODO is empty string valid?
    }

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
