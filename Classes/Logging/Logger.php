<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Logging;

use Psr\Log\LoggerTrait;
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
     * Save messages for output
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Initialize
     *
     * @param string $className
     */
    public function __construct(string $className)
    {
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
        $this->logger->log($level, $message, $context);
    }

    /**
     * Get instance
     *
     * @param string $clasName
     * @return Logger
     */
    public static function getInstance(string $clasName): Logger
    {
        return GeneralUtility::makeInstance(__CLASS__, $clasName);
    }
}
