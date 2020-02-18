<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Validation\Validator;

use Pixelant\PxaPmImporter\Validation\ValidationResult;

/**
 * Class RequiredValidator
 * @package Pixelant\PxaPmImporter\Domain\Validation
 */
class RequiredValidator extends AbstractValidator
{
    /**
     * Validate given value
     *
     * @param array $importRow
     * @param string $property
     * @return ValidationResult
     */
    public function validate(array $importRow, string $property): ValidationResult
    {
        if (empty($importRow[$property] ?? null)) {
            $this->result->setPassed(false);
            $this->result->setError('Value could not be empty');
        }

        return $this->result;
    }
}
