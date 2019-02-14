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
            $this->addError('Value "' . $value . '" is not numeric');

            return false;
        }

        return $valid;
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
