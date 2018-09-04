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
     * Check if numeric
     *
     * @param $value
     * @return bool
     */
    public function isValid($value): bool
    {
        if ($this->isRequired() && empty($value)) {
            $this->validationError = 'Property "' . $this->property . '" value is required';

            return false;
        }

        // Empty value is valid if not required
        if (!empty($value) && !is_numeric($value)) {
            $this->validationError = 'Property "' . $this->property . '" value should be numeric';
            return false;
        }

        return true;
    }

    /**
     * Process
     *
     * @param $value
     * @return int
     */
    public function postProcess($value): int
    {
        return intval($value);
    }
}
