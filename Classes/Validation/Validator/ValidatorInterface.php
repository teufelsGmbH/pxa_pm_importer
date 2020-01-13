<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Validation\Validator;

use Pixelant\PxaPmImporter\Validation\ValidationResult;

/**
 * Interface ProcessorFieldValueValidatorInterface
 * @package Pixelant\PxaPmImporter\Domain\Validation
 */
interface ValidatorInterface
{
    /**
     * Validate given value and return result
     *
     * @param $value
     * @return ValidationResult
     */
    public function validate($value): ValidationResult;
}
