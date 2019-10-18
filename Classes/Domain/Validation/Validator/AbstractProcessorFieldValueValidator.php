<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Validation\Validator;

/**
 * Class AbstractProcessorFieldValueValidator
 * @package Pixelant\PxaPmImporter\Domain\Validation
 */
abstract class AbstractProcessorFieldValueValidator implements ProcessorFieldValueValidatorInterface
{
    /**
     * @var int
     */
    protected $severity = 0;

    /**
     * @var string
     */
    protected $message = '';

    /**
     * Return result error on validation error
     *
     * @return int
     */
    public function getSeverity(): int
    {
        return $this->severity;
    }

    /**
     * Return error
     *
     * @return string
     */
    public function getValidationError(): string
    {
        return $this->message;
    }

    /**
     * Create status with error
     *
     * @param string $message
     */
    protected function error(string $message): void
    {
        $this->message = $message;
        $this->severity = self::ERROR;
    }

    /**
     * Create status with critical error
     *
     * @param string $message
     */
    protected function critical(string $message): void
    {

        $this->message = $message;
        $this->severity = self::CRITICAL;
    }

    /**
     * Create status with warning
     *
     * @param string $message
     */
    protected function warning(string $message): void
    {

        $this->message = $message;
        $this->severity = self::WARNING;
    }
}
