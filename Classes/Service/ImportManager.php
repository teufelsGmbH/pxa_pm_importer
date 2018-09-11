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
        $this->logger = Logger::getInstance(__CLASS__);
    }

    /**
     * Execute logic for single import
     *
     * @param Import $import
     */
    public function execute(Import $import): void
    {
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

            $this->logger->info(sprintf(
                'Start import for import configuration "%s" with UID - %d',
                $import->getName(),
                $import->getUid()
            ));
            $importer->preImport($source, $import, $singleImporterConfiguration);
            $importer->start($source, $import, $singleImporterConfiguration);
            $importer->postImport($import);

            $this->logger->info(sprintf(
                'End import for import configuration "%s" with UID - %d',
                $import->getName(),
                $import->getUid()
            ));
        }

        $this->emitSignal('afterImportExecute', [$import]);

        // Set last execution time
        $import->setLastExecution(new \DateTime());
        $this->importRepository->update($import);
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
}
