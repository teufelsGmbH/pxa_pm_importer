<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Validation\Validator;

use Pixelant\PxaPmImporter\Domain\Validation\ValidationStatusInterface;

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
            $this->error('Value can not be empty for required property');

            return false;
        }

        return true;
    }
}
