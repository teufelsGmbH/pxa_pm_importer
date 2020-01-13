<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Validation\Validator;

use Pixelant\PxaPmImporter\Validation\ValidationResult;

/**
 * Class AbstractProcessorFieldValueValidator
 * @package Pixelant\PxaPmImporter\Domain\Validation
 */
abstract class AbstractValidator implements ValidatorInterface
{
    /**
     * @var ValidationResult
     */
    protected $result;

    /**
     * @param ValidationResult $result
     */
    public function __construct(ValidationResult $result)
    {
        $this->result = $result;
    }
}
