<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Validation\Validator;

use Pixelant\PxaPmImporter\Processors\FieldProcessorInterface;

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
     * @param FieldProcessorInterface $processor
     * @return bool
     */
    public function validate($value, FieldProcessorInterface $processor): bool
    {
        if (empty($value)) {
            $this->error('Value can not be empty.');

            return false;
        }

        return true;
    }
}
