<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Validation;

/**
 * Class ErrorMessage
 * @package Pixelant\PxaPmImporter\Domain\Validation
 */
class ValidationStatus implements ValidationStatusInterface
{
    /**
     * @var string
     */
    protected $message = '';

    /**
     * @var int
     */
    protected $severity = self::WARNING;

    /**
     * Initialize
     *
     * @param string $message
     * @param int $severity
     */
    public function __construct(string $message = '', int $severity = self::WARNING)
    {
        $this->setMessage($message);
        $this->setSeverity($severity);
    }

    /**
     * Get error message
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Set the error message
     *
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * Get severity
     *
     * @return int
     */
    public function getSeverity(): int
    {
        return $this->severity;
    }

    /**
     * Set severity
     *
     * @param int $severity
     */
    public function setSeverity(int $severity): void
    {
        $this->severity = $severity;
    }
}
