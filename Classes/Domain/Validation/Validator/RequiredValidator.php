<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Validation;

/**
 * Class RequiredValidator
 * @package Pixelant\PxaPmImporter\Domain\Validation
 */
class RequiredValidator extends AbstractProcessorFieldValueValidator
{
    /**
     * Validate given value
     *
     * @param $value
     * @return bool
     */
    public function validate($value): bool
    {
        if (empty($value)) {
            $this->validationStatus = $this->createValidationStatus(
                'Value "' . $value . '" can not be empty',
                ValidationStatusInterface::ERROR
            );

            return false;
        }

        return true;
    }
}
