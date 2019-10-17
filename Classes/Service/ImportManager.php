<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service;

use Pixelant\PxaPmImporter\Context\ImportContext;
use Pixelant\PxaPmImporter\Exception\InvalidConfigurationSourceException;
use Pixelant\PxaPmImporter\Logging\Logger;
use Pixelant\PxaPmImporter\Service\Configuration\ConfigurationServiceFactory;
use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use Pixelant\PxaPmImporter\Service\Source\SourceFactory;
use Pixelant\PxaPmImporter\Service\Source\SourceInterface;
use Pixelant\PxaPmImporter\Traits\EmitSignalTrait;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Importer
 * @package Pixelant\PxaPmImporter\Service
 */
class ImportManager
{
    use EmitSignalTrait;

    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var ImportContext
     */
    protected $context = null;

    /**
     * @var SourceFactory
     */
    protected $sourceFactory = null;

    /**
     * ImportManager constructor.
     * @param SourceFactory $sourceFactory
     */
    public function __construct(SourceFactory $sourceFactory)
    {
        $this->sourceFactory = $sourceFactory;
    }

    /**
     * @param ImportContext $context
     */
    public function injectContext(ImportContext $context)
    {
        $this->context = $context;
    }

    /**
     * Execute logic for single import
     *
     * @param string $configuration
     */
    public function execute(string $configuration): void
    {
        $this->boot($configuration);

        $this->emitSignal(__CLASS__, 'beforeImportExecute', [$configuration]);

        $sources = $this->context->getConfigurationService()->getSourcesConfiguration();
        $importers = $this->context->getConfigurationService()->getImportersConfiguration();
        $multipleSources = count($sources) > 1;

        // Run import for every source
        foreach ($sources as $source) {
            $sourceInstance = $this->sourceFactory->createSource($source);

            // Run importers for each source
            foreach ($importers as $importerClass => $singleImporterConfiguration) {
                /** @var ImporterInterface $importer */
                $importer = GeneralUtility::makeInstance($importerClass, $this->logger);
                if (!($importer instanceof ImporterInterface)) {
                    // @codingStandardsIgnoreStart
                    throw new \UnexpectedValueException('Class "' . $importerClass . '" should be instance of ImporterInterface', 1536044275945);
                    // @codingStandardsIgnoreEnd
                }

                // Write to log about import start
                $this->logger->info(sprintf(
                    'Start import using source "%s", at %s',
                    $source,
                    date('G-i-s')
                ));

                $startTime = time();
                $importer->preImport($source, $singleImporterConfiguration);
                $importer->start($source, $singleImporterConfiguration);
                $importer->postImport();

                $this->logger->info('Memory usage "' . MainUtility::getMemoryUsage() . '"');
                $this->logger->info('Import duration - ' . $this->getDurationTime($startTime));

                // Log info about import end
                $this->logger->info(sprintf(
                    'End import using source "%s", at %s',
                    $source,
                    date('G-i-s')
                ));
            }
        }

        if ($multipleSources) {
            $this->logger->info(
                'Full import duration - ' . $this->getDurationTime($this->context->getImportStartTimeStamp())
            );
        }

        $this->emitSignal(__CLASS__, 'afterImportExecute', [$configuration]);
    }

    /**
     * Get errors that appears while importing
     *
     * @return array
     */
    public function getErrors(): array
    {
        return Logger::getErrorMessages();
    }

    /**
     * File to log with all messages
     *
     * @return string
     */
    public function getLogFilePath(): string
    {
        return $this->logger->getLogFilePath();
    }

    /**
     * Get duration time
     *
     * @param int $startTime
     * @return string
     */
    protected function getDurationTime(int $startTime): string
    {
        $init = time() - $startTime;
        $hours = floor($init / 3600);
        $minutes = floor(($init / 60) % 60);
        $seconds = $init % 60;

        return sprintf('%s hours, %s minutes and %s seconds', $hours, $minutes, $seconds);
    }

    /**
     * Boot before start import
     *
     * @param string $configurationSource
     */
    protected function boot(string $configurationSource): void
    {
        $this->initializeImportConfigurationAndSetInContext($configurationSource);
        $this->initLogger();
    }

    /**
     * Initialize import configuration and context
     *
     * @param string $configurationSource
     */
    protected function initializeImportConfigurationAndSetInContext(string $configurationSource): void
    {
        $configurationService = ConfigurationServiceFactory::getConfiguration($configurationSource);

        $this->context->setConfigurationService($configurationService);
        $this->context->setImportConfigurationSource($configurationSource);
    }

    /**
     * Init logger with custom path
     */
    protected function initLogger(): void
    {
        $customPath = $this->context->getConfigurationService()->getLogPath();
        $this->logger = Logger::getInstance(__CLASS__, $customPath);
    }
}
