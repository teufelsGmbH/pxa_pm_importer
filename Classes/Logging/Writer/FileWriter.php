<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Logging\Writer;

use TYPO3\CMS\Core\Log\Exception\InvalidLogWriterConfigurationException;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\FileWriter as LogFileWriter;
use TYPO3\CMS\Core\Log\Writer\WriterInterface;

class FileWriter extends LogFileWriter
{
    /**
     * Import log file, is used for all log file writers
     * Use one for all in order to get all logs into one file per
     * @var string
     */
    private static $importLogFile = null;

    /**
     * Sets the path to the log file.
     *
     * @param string $relativeLogFile path to the log file, relative to PATH_site
     * @return WriterInterface
     * @throws InvalidLogWriterConfigurationException
     */
    public function setLogFile($relativeLogFile)
    {
        if (self::$importLogFile === null) {
            // Generate log with date
            $pi = pathinfo($relativeLogFile);
            $relativeLogFile = sprintf(
                '%s/%s_%s.%s',
                $pi['dirname'],
                $pi['filename'],
                $this->getLogFileDate(),
                $pi['extension']
            );

            self::$importLogFile = $relativeLogFile;
        }

        return parent::setLogFile(self::$importLogFile);
    }

    /**
     * Writes the log record
     *
     * @param LogRecord $record Log record
     * @return WriterInterface $this
     * @throws \RuntimeException
     */
    public function writeLog(LogRecord $record)
    {
        $levelName = LogLevel::getName($record->getLevel());
        $data = '';
        $recordData = $record->getData();
        if (!empty($recordData)) {
            // According to PSR3 the exception-key may hold an \Exception
            // Since json_encode() does not encode an exception, we run the _toString() here
            if (isset($recordData['exception']) && $recordData['exception'] instanceof \Exception) {
                $recordData['exception'] = (string)$recordData['exception'];
            }
            $data = '- ' . json_encode($recordData);
        }

        // Skip vendor and extension name
        $componentParts = explode('.', $record->getComponent());
        $component = implode('.', array_slice($componentParts, 2));

        $message = sprintf(
            '[%s] component="%s": %s %s',
            $levelName,
            $component,
            $record->getMessage(),
            $data
        );

        if (false === fwrite(self::$logFileHandles[$this->logFile], $message . LF)) {
            throw new \RuntimeException('Could not write log record to log file', 1345036335);
        }

        return $this;
    }

    /**
     * @return string
     */
    protected function getLogFileDate(): string
    {
        return date('Y-m-d_H:i:s');
    }
}
