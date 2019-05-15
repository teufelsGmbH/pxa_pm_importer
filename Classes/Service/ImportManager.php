<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service;

use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Domain\Repository\ImportRepository;
use Pixelant\PxaPmImporter\Exception\InvalidConfigurationSourceException;
use Pixelant\PxaPmImporter\Logging\Logger;
use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use Pixelant\PxaPmImporter\Service\Source\SourceInterface;
use Pixelant\PxaPmImporter\Traits\EmitSignalTrait;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;

/**
 * Class Importer
 * @package Pixelant\PxaPmImporter\Service
 */
class ImportManager
{
    use EmitSignalTrait;

    /**
     * @var ImportRepository
     */
    protected $importRepository = null;

    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * Initialize
     *
     * @param RepositoryInterface $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->importRepository = $repository;
    }

    /**
     * Execute logic for single import
     *
     * @param Import $import
     */
    public function execute(Import $import): void
    {
        $this->initLogger($import);

        $this->emitSignal('beforeImportExecute', [$import]);

        $source = $this->resolveImportSource($import);
        $importersConfiguration = $import->getConfigurationService()->getImportersConfiguration();

        foreach ($importersConfiguration as $importerClass => $singleImporterConfiguration) {
            /** @var ImporterInterface $importer */
            $importer = GeneralUtility::makeInstance($importerClass, $this->logger);
            if (!($importer instanceof ImporterInterface)) {
                // @codingStandardsIgnoreStart
                throw new \UnexpectedValueException('Class "' . $importerClass . '" should be instance of ImporterInterface', 1536044275945);
                // @codingStandardsIgnoreEnd
            }

            // Write to log about import start
            $this->logger->info(sprintf(
                'Start import for import configuration "%s" with UID - %d, at %s',
                $import->getName(),
                $import->getUid(),
                date('G-i-s')
            ));

            $startTime = time();
            $importer->preImport($source, $import, $singleImporterConfiguration);
            $importer->start($source, $import, $singleImporterConfiguration);
            $importer->postImport($import);

            // Log info about import end
            $this->logger->info(sprintf(
                'End import for import configuration "%s" with UID - %d, at %s',
                $import->getName(),
                $import->getUid(),
                date('G-i-s')
            ));
            $this->logger->info('Memory usage "' . MainUtility::getMemoryUsage() . '"');
            $this->logger->info('Import duration - ' . $this->getDurationTime($startTime));
        }

        $this->emitSignal('afterImportExecute', [$import]);
    }

    /**
     * Get errors that appears while importing
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->logger->getErrorMessages();
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
     * Resolve source
     * @TODO can we have multiple sources ???
     *
     * @param Import $import
     * @return SourceInterface
     */
    protected function resolveImportSource(Import $import): SourceInterface
    {
        $sourceConfiguration = $import->getConfigurationService()->getSourceConfiguration();
        foreach ($sourceConfiguration as $sourceClass => $configuration) {
            /** @var SourceInterface $source */
            $source = GeneralUtility::makeInstance($sourceClass);
            if (!($source instanceof SourceInterface)) {
                // @codingStandardsIgnoreStart
                throw new \UnexpectedValueException('Class "' . $sourceClass . '" should be instance of SourceInterface', 1536044243356);
                // @codingStandardsIgnoreEnd
            }
            $source->initialize($configuration);

            return $source;
        }

        // @codingStandardsIgnoreStart
        throw new InvalidConfigurationSourceException('It\'s not possible to resolve source in "' . $import->getName() . '" configuration.', 1536043244442);
        // @codingStandardsIgnoreEnd
    }

    /**
     * Init logger with custom path
     *
     * @param Import $import
     */
    protected function initLogger(Import $import): void
    {
        $customPath = $import->getConfigurationService()->getLogCustomPath();
        $this->logger = Logger::getInstance(__CLASS__, $customPath);
    }
}
