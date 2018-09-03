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
     * @return int
     */
    public function postProcess($value): int
    {
        return intval($value);
    }
}
