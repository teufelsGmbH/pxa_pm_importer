<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Validation;

/**
 * Class ErrorMessageInterface
 * @package Pixelant\PxaPmImporter\Domain\Validation
 */
interface ValidationStatusInterface
{
    /**
     * Severity levels
     */
    const WARNING = 1; // Error occurred, property won't be set, but can continue
    const ERROR = 2; // Error occurred and should skip current import row
    const CRITICAL = 3; // Critical validation error, import should stop

    /**
     * Get error message
     *
     * @return string
     */
    public function getMessage(): string;

    /**
     * Set the error message
     *
     * @param string $message
     */
    public function setMessage(string $message): void;

    /**
     * Get severity
     *
     * @return int
     */
    public function getSeverity(): int;

    /**
     * Set severity
     *
     * @param int $severity
     */
    public function setSeverity(int $severity): void;
}
