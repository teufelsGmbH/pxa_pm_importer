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
     * @param array $importRow Full import row
     * @param string $property Validation property
     * @return ValidationResult
     */
    public function validate(array $importRow, string $property): ValidationResult;
}
