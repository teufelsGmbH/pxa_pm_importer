<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Validation\Validator;

use Pixelant\PxaPmImporter\Domain\Validation\ValidationStatus;
use Pixelant\PxaPmImporter\Domain\Validation\ValidationStatusInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AbstractProcessorFieldValueValidator
 * @package Pixelant\PxaPmImporter\Domain\Validation
 */
abstract class AbstractProcessorFieldValueValidator implements ProcessorFieldValueValidatorInterface
{
    /**
     * @var ValidationStatusInterface
     */
    protected $validationStatus = null;

    /**
     * Return result error on validation error
     *
     * @return ValidationStatusInterface
     */
    public function getValidationStatus(): ValidationStatusInterface
    {
        if ($this->validationStatus === null) {
            // create default status
            $this->validationStatus = $this->createValidationStatus('', ValidationStatusInterface::OK);
        }

        return $this->validationStatus;
    }

    /**
     * Create status with error
     *
     * @param string $message
     */
    protected function error(string $message): void
    {
        $this->validationStatus = $this->createValidationStatus(
            $message,
            ValidationStatusInterface::ERROR
        );
    }

    /**
     * Create status with critical error
     *
     * @param string $message
     */
    protected function critical(string $message): void
    {
        $this->validationStatus = $this->createValidationStatus(
            $message,
            ValidationStatusInterface::CRITICAL
        );
    }

    /**
     * Create status with warning
     *
     * @param string $message
     */
    protected function warning(string $message): void
    {
        $this->validationStatus = $this->createValidationStatus(
            $message,
            ValidationStatusInterface::WARNING
        );
    }

    /**
     * Create validation status
     *
     * @param string $message
     * @param int $severity
     * @return ValidationStatus
     */
    protected function createValidationStatus(string $message, int $severity): ValidationStatus
    {
        return GeneralUtility::makeInstance(
            ValidationStatus::class,
            $message,
            $severity
        );
    }
}
