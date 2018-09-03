<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service;

use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Domain\Repository\ImportRepository;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;

/**
 * Class Importer
 * @package Pixelant\PxaPmImporter\Service
 */
class ImportManager
{
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
        // Set last execution time
        $import->setLastExecution(new \DateTime());
        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($import,'Debug',16);
        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($this->importRepository,'Debug',16);


        $this->importRepository->update($import);
    }
}
