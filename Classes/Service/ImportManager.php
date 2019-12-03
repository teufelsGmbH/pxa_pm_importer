<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service;

use Pixelant\PxaPmImporter\Context\ImportContext;
use Pixelant\PxaPmImporter\Logging\Logger;
use Pixelant\PxaPmImporter\Service\Configuration\ConfigurationServiceFactory;
use Pixelant\PxaPmImporter\Service\Importer\Builder\ImporterBuilderInterface;
use Pixelant\PxaPmImporter\Service\Importer\ImporterDirector;
use Pixelant\PxaPmImporter\Service\Source\SourceFactory;
use Pixelant\PxaPmImporter\Traits\EmitSignalTrait;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

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
     * @var ConfigurationServiceFactory
     */
    protected $configurationFactory = null;

    /**
     * @var ImporterDirector
     */
    protected $importerDirector = null;

    /**
     * @var ObjectManager
     */
    protected $objectManager = null;

    /**
     * ImportManager constructor.
     * @param SourceFactory $sourceFactory
     * @param ImporterDirector $importerDirector
     * @param ConfigurationServiceFactory $configurationFactory
     */
    public function __construct(
        SourceFactory $sourceFactory,
        ImporterDirector $importerDirector,
        ConfigurationServiceFactory $configurationFactory
    ) {
        $this->sourceFactory = $sourceFactory;
        $this->importerDirector = $importerDirector;
        $this->configurationFactory = $configurationFactory;
    }

    /**
     * @param ImportContext $context
     */
    public function injectContext(ImportContext $context)
    {
        $this->context = $context;
    }

    /**
     * @param ObjectManager $objectManager
     */
    public function injectObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
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
        foreach ($sources as $source => $sourceConfiguration) {
            $sourceInstance = $this->sourceFactory->createSource($source, $sourceConfiguration);

            // Run importers for each source
            foreach ($importers as $importName => $importConfiguration) {
                if ($importConfiguration['disable'] ?? false) {
                    continue;
                }
                // Initialize importer
                $importer = $this->importerDirector->build($importConfiguration);

                // Write to log about import start
                $this->logger->info(sprintf(
                    'Start import using source "%s" and importer "%s", at %s',
                    $source,
                    $importName,
                    date('G-i-s')
                ));

                // Save time
                $startTime = time();

                // Set context info about current importer and source
                $this->context->setCurrentImportInfo(
                    $source,
                    $sourceInstance,
                    $importName,
                    $importer
                );

                // Execute importer
                $importer
                    ->initialize($sourceInstance, $importConfiguration)
                    ->execute();

                // Reset context info about source and importer
                $this->context->resetCurrentImportInfo();

                // Write to log about memory usage and duration
                $this->logger->info('Memory usage "' . MainUtility::getMemoryUsage() . '"');
                $this->logger->info('Import duration - ' . $this->getDurationTime($startTime));

                // Log info about import end
                $this->logger->info(sprintf(
                    'End import using source "%s" and importer "%s", at %s',
                    $source,
                    $importName,
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
        $configurationService = $this->configurationFactory->createConfiguration($configurationSource);

        $this->context->setConfigurationService($configurationService);
        $this->context->setImportConfigurationSource($configurationSource);
    }

    /**
     * Init logger with custom path
     */
    protected function initLogger(): void
    {
        $customPath = $this->context->getConfigurationService()->getLogPath();
        $severity = $this->context->getConfigurationService()->getLogSeverity();

        Logger::resetErrors();
        $this->logger = Logger::getInstance(__CLASS__, $customPath, $severity);
    }
}
