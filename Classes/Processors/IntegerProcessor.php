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
        $valid = parent::isValid($value);

        // Empty value is valid if not required
        if (!empty($value) && !is_numeric($value)) {
            $this->validationErrors = 'Property "' . $this->property . '" value should be numeric';

            return false;
        }

        return $valid;
    }

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
