<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Logging;

use Pixelant\PxaPmImporter\Logging\Writer\FileWriter;
use Psr\Log\LoggerTrait;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Logger
 * @package Pixelant\PxaPmImporter\Logging
 */
class Logger
{
    use LoggerTrait;

    /**
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger = null;

    /**
     * Save some messages
     *
     * @var array
     */
    protected $errorMessages = [];

    /**
     * Initialize
     *
     * @param string $className
     * @param bool $dontReplaceClassNameSpace By default replace extension name in class name, in order to get all log records in one file, if logger is used in another extension
     */
    public function __construct(string $className, bool $dontReplaceClassNameSpace = false)
    {
        if (false === $dontReplaceClassNameSpace) {
            $classParts = GeneralUtility::trimExplode('\\', $className, true);
            if (count($classParts) > 2) {
                $classParts[1] = 'PxaPmImporter';
            }
            $className = implode('\\', $classParts);
        }

        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger($className);
    }

    /**
     * Log message
     *
     * @param $level
     * @param $message
     * @param array $context
     */
    public function log($level, $message, array $context = []): void
    {
        $errorLevel = [LogLevel::EMERGENCY, LogLevel::CRITICAL, LogLevel::ERROR];

        // Save errors, but max 10
        if (in_array(LogLevel::normalizeLevel($level), $errorLevel) && count($this->errorMessages) <= 10) {
            $this->errorMessages[] = $message;
        }

        $this->logger->log($level, $message, $context);
    }

    /**
     * Get errors
     */
    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }

    /**
     * Get path to log file
     *
     * @return string
     */
    public function getLogFilePath(): string
    {
        foreach ($this->logger->getWriters() as $writers) {
            foreach ($writers as $writer) {
                if ($writer instanceof FileWriter) {
                    return $writer->getLogFile();
                }
            }
        }

        return '';
    }

    /**
     * Get instance
     *
     * @param string $className
     * @return Logger
     */
    public static function getInstance(string $clasName): Logger
    {
        return GeneralUtility::makeInstance(__CLASS__, $clasName);
    }
}
