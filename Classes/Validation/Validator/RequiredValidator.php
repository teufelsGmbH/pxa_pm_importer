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
     * @param $value
     * @return ValidationResult
     */
    public function validate($value): ValidationResult
    {
        if (empty($value)) {
            $this->result->setPassed(false);
            $this->result->setError('Value could not be empty');
        }

        return $this->result;
    }
}
