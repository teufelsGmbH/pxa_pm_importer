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
     * Set property according the "outputFormat"
     *
     * @param $value
     */
    public function process($value): void
    {
        $this->checkConfiguration();

        $inputFormat = $this->configuration['inputFormat'];
        $outputFormat = $this->configuration['outputFormat'];
        $inputDate = \DateTime::createFromFormat($inputFormat . '|', $value);

        $this->simplePropertySet($inputDate->format($outputFormat));
    }

    /**
     * Checks if configuration is valid
     */
    protected function checkConfiguration(): void
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
    }
}
