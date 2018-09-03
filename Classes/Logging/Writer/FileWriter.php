<?php
declare(strict_types=1);
namespace Pixelant\PxaPmImporter\Logging\Writer;

use TYPO3\CMS\Core\Log\Exception\InvalidLogWriterConfigurationException;
use TYPO3\CMS\Core\Log\Writer\FileWriter as LogFileWriter;
use TYPO3\CMS\Core\Log\Writer\WriterInterface;

class FileWriter extends LogFileWriter
{
    /**
     * Sets the path to the log file.
     *
     * @param string $relativeLogFile path to the log file, relative to PATH_site
     * @return WriterInterface
     * @throws InvalidLogWriterConfigurationException
     */
    public function setLogFile($relativeLogFile)
    {
        // Generate log with date
        $pi = pathinfo($relativeLogFile);
        $relativeLogFile = sprintf(
            '%s/%s_%s.%s',
            $pi['dirname'],
            $pi['filename'],
            $this->getLogFileDate(),
            $pi['extension']
        );

        return parent::setLogFile($relativeLogFile);
    }

    /**
     * @return string
     */
    protected function getLogFileDate(): string
    {
        return date('Y-m-d_H_i');
    }
}
