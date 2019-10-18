<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Validation\Validator;

use Pixelant\PxaPmImporter\Processors\FieldProcessorInterface;

/**
 * Interface ProcessorFieldValueValidatorInterface
 * @package Pixelant\PxaPmImporter\Domain\Validation
 */
interface ProcessorFieldValueValidatorInterface
{
    const WARNING = 1; // Error occurred, property won't be set, but can continue
    const ERROR = 2; // Error occurred and should skip current import row
    const CRITICAL = 3; // Critical validation error, import should stop

    /**
     * Validate given value
     *
     * @param $value
     * @param FieldProcessorInterface $processor
     * @return bool
     */
    public function validate($value, FieldProcessorInterface $processor): bool;

    /**
     * Return result error level on validation error
     *
     * @return int
     */
    public function getSeverity(): int;

    /**
     * Return validation error
     *
     * @return string
     */
    public function getValidationError(): string;
}
