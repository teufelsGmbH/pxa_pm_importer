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
     * @return mixed|string
     */
    public function preProcess($value)
    {
        $value = parent::preProcess($value);
        return str_replace(',', '.', $value);
    }

    /**
     * Check if numeric
     *
     * @param $value
     * @param string $fieldName
     * @return bool
     */
    public function isValid($value, string $fieldName): bool
    {
        if (parent::isValid($value, $fieldName)) {
            // Empty value is valid if not required
            if (!empty($value) && !is_numeric($value)) {
                $this->validationError = 'Field "' . $fieldName . '" should be numeric';
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Process
     *
     * @param $value
     * @return float
     */
    public function postProcess($value): float
    {
        return (float)$value;
    }
}
