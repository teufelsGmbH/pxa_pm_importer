<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Exception\InvalidProcessorConfigurationException;

/**
 * Class DateTimeProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class DateTimeProcessor extends AbstractFieldProcessor
{
    /**
     * Prepare for process
     *
     * @param mixed $value
     */
    public function preProcess(&$value): void
    {
        if (empty($this->configuration['inputFormat'])) {
            // @codingStandardsIgnoreStart
            throw new InvalidProcessorConfigurationException('Missing "inputFormat" of processor configuration. Name - "' . $this->property . '"', 1538032831);
            // @codingStandardsIgnoreEnd
        }

        if (empty($this->configuration['outputFormat'])) {
            // @codingStandardsIgnoreStart
            throw new InvalidProcessorConfigurationException('Missing "outputFormat" of processor configuration. Name - "' . $this->property . '"', 1538032831);
            // @codingStandardsIgnoreEnd
        }

        parent::preProcess($value);
    }

    /**
     * Check if numeric
     *
     * @param $value
     * @return bool
     */
    public function isValid($value): bool
    {
        $inputFormat = $this->configuration['inputFormat'];
        $inputDate = \DateTime::createFromFormat($inputFormat . '|', $value);
        if ($inputDate && $inputDate->format($inputFormat) === $value) {
            return parent::isValid($value);
        } else {
            $this->addError(
                sprintf(
                    'Property "%s" - can\'t create a DateTime from "%s" with format "%s" (%s)',
                    $this->property,
                    $value,
                    $inputFormat,
                    $this->getDateTimeErrorString()
                )
            );
            return false;
        }
    }

    /**
     * Set as float
     *
     * @param $value
     */
    public function process($value): void
    {
        $inputFormat = $this->configuration['inputFormat'];
        $outputFormat = $this->configuration['outputFormat'];
        $inputDate = \DateTime::createFromFormat($inputFormat . '|', $value);
        $this->simplePropertySet($inputDate->format($outputFormat));
    }

    protected function getDateTimeErrorString(): string
    {
        $errorString = '';
        $lastErrors = \DateTime::getLastErrors();

        if (is_array($lastErrors['errors']) && $lastErrors['error_count'] > 0) {
            $errorString = '"' . implode('", "', $lastErrors['errors']) . '"';
        }

        return $errorString;
    }
}
