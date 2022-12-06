<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Logging;

use Pixelant\PxaPmImporter\Logging\Writer\FileWriter;
use Psr\Log\LoggerTrait;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\Logger as CoreLogger;
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
     * @var CoreLogger
     */
    protected $logger = null;

    /**
     * Name of class of object that is logging
     *
     * @var string
     */
    protected $loggingClass = '';

    /**
     * Save some messages
     *
     * @var array
     */
    protected static $errorMessages = [];

    /**
     * Lower log level that need to be logged
     *
     * @var int
     */
    protected static $logSeverity = LogLevel::INFO;

    /**
     * Initialize
     *
     * @param string $className
     * @param string $customLogPath
     * @param int|null $severity
     */
    public function __construct(string $className, string $customLogPath = null, int $severity = null)
    {
        $classParts = GeneralUtility::trimExplode('\\', $className, true);

        // Override extension name in order to get our file writer if called outside extension
        if (count($classParts) >= 2) {
            $classParts[0] = 'Pixelant';
            $classParts[1] = 'PxaPmImporter';
        }
        $this->loggingClass = implode('\\', $classParts);

        // If required to configure logger
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['Pixelant']['PxaPmImporter']['writerConfiguration'])
            || !empty($customLogPath)
            || !empty($severity)
        ) {
            $this->configureLogger($customLogPath, $severity);
        }
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
        if ($logger = $this->getLogger($level)) {
            $errorLevel = [LogLevel::EMERGENCY, LogLevel::CRITICAL, LogLevel::ERROR];

            // Save errors, but max 10 and only unique
            if (in_array(LogLevel::normalizeLevel($level), $errorLevel) && count(static::$errorMessages) <= 10) {
                $messageHash = md5($message);
                if (!array_key_exists($messageHash, static::$errorMessages)) {
                    static::$errorMessages[$messageHash] = $message;
                }
            }

            $logger->log($level, $message, $context);
        }
    }

    /**
     * Get path to log file
     *
     * @return string
     */
    public function getLogFilePath(): string
    {
        if ($this->logger !== null) {
            foreach ($this->logger->getWriters() as $writers) {
                foreach ($writers as $writer) {
                    if ($writer instanceof FileWriter) {
                        return $writer->getLogFile();
                    }
                }
            }
        }

        return '';
    }

    /**
     * Get errors
     */
    public static function getErrorMessages(): array
    {
        return static::$errorMessages;
    }

    /**
     * Reset errors
     *
     * @return array
     */
    public static function resetErrors(): void
    {
        static::$errorMessages = [];
    }

    /**
     * Get instance
     *
     * @param string $className
     * @param string $customLogPath
     * @param int $severity
     * @return Logger
     */
    public static function getInstance(string $className, string $customLogPath = null, int $severity = null): Logger
    {
        return GeneralUtility::makeInstance(__CLASS__, $className, $customLogPath, $severity);
    }

    /**
     * Configure logger
     *
     * @param string|null $customPath
     * @param int|null $severity
     */
    protected function configureLogger(string $customPath = null, int $severity = null): void
    {
        $customPath = $customPath ?? (Environment::getVarPath() . '/log/pm_importer.log');
        static::$logSeverity = $severity ?? LogLevel::INFO;

        LogLevel::validateLevel(LogLevel::normalizeLevel(static::$logSeverity));

        $GLOBALS['TYPO3_CONF_VARS']['LOG']['Pixelant']['PxaPmImporter']['writerConfiguration'] = [
            static::$logSeverity => [
                FileWriter::class => [
                    'logFile' => $customPath
                ]
            ]
        ];
    }

    /**
     * Get logger
     *
     * @param $level
     * @return CoreLogger|null
     */
    protected function getLogger($level): ?CoreLogger
    {
        $level = (int)LogLevel::normalizeLevel($level);

        if ($this->logger === null && $level <= static::$logSeverity) {
            $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger($this->loggingClass);
        }

        return $this->logger;
    }
}
