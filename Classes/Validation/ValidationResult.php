<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Validation;

/**
 * Hold result of validation
 *
 * @package Pixelant\PxaPmImporter\Validation
 */
class ValidationResult
{
    /**
     * @var bool
     */
    protected $passed = true;

    /**
     * Error message
     *
     * @var string
     */
    protected $error = '';

    /**
     * @return bool
     */
    public function passed(): bool
    {
        return $this->passed;
    }

    /**
     * @param bool $passed
     */
    public function setPassed(bool $passed): void
    {
        $this->passed = $passed;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @param string $error
     */
    public function setError(string $error): void
    {
        $this->error = $error;
    }
}
