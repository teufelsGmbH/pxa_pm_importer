<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service;

use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Domain\Repository\ImportRepository;
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
        $this->emitSignal('beforeImportExecute', [$import]);

        $source = $this->resolveImportSource($import);
        $importersConfiguration = $import->getConfigurationService()->getImportersConfiguration();

        foreach ($importersConfiguration as $importerClass => $singleImporterConfiguration) {
            /** @var ImporterInterface $importer */
            $importer = GeneralUtility::makeInstance($importerClass);
            $importer->start($source, $import, $singleImporterConfiguration);
        }

        $this->emitSignal('afterImportExecute', [$import]);

        // Set last execution time
        $import->setLastExecution(new \DateTime());
        $this->importRepository->update($import);
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
            $source->initialize($configuration);

            return $source;
        }
    }
}
