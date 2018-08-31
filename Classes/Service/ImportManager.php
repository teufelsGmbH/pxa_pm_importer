<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service;

use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Domain\Repository\ImportRepository;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;

/**
 * Class Importer
 * @package Pixelant\PxaPmImporter\Service
 */
class ImportManager
{
    /**
     * @var PersistenceManager
     */
    protected $persistenceManagerManager = null;

    /**
     * @var ImportRepository
     */
    protected $importRepository = null;

    /**
     * Initialize
     *
     * @param PersistenceManagerInterface $persistenceManager
     * @param RepositoryInterface $repository
     */
    public function __construct(PersistenceManagerInterface $persistenceManager, RepositoryInterface $repository)
    {
        $this->persistenceManagerManager = $persistenceManager;
        $this->importRepository = $repository;
    }

    /**
     * Start import for configuration
     *
     * @param Import $import
     */
    public function runScheduled(Import $import): void
    {
        if ($this->shouldExecute($import)) {
            $this->execute($import);

            // Set last execution time
            $import->setLastExecution(new \DateTime());
            $import->setNextExecution($import->calculateNextExecutionTime());
            $this->importRepository->update($import);

            $this->persistenceManagerManager->persistAll();
        }
    }

    /**
     * Execute logic for single import
     *
     * @param Import $import
     */
    protected function execute(Import $import): void
    {

    }

    /**
     * Check if import should run on this execution
     *
     * @param Import $import
     * @return bool
     */
    protected function shouldExecute(Import $import): bool
    {
        if ($import->isSingleTimeExecution()) {
            return $import->getLastExecution() === null;
        } else {
            $nextExecution = $import->getNextExecution() ?? $import->calculateNextExecutionTime();

            return $nextExecution->getTimestamp() <= $GLOBALS['EXEC_TIME'];
        }
    }
}
