<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Validation;

/**
 * Interface ProcessorFieldValueValidatorInterface
 * @package Pixelant\PxaPmImporter\Domain\Validation
 */
interface ProcessorFieldValueValidatorInterface
{
    /**
     * Validate given value
     *
     * @param $value
     * @return bool
     */
    public function validate($value): bool;

    /**
     * Return result error on validation error
     *
     * @return ValidationStatusInterface
     */
    public function getValidationStatus(): ValidationStatusInterface;
}
