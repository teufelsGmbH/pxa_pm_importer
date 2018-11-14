<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class ImportRepository
 * @package Pixelant\PxaPmImporter\Domain\Repository
 */
class ImportRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = ['sorting' => QueryInterface::ORDER_ASCENDING];

    /**
     * Default settings
     */
    public function initializeObject(): void
    {
        $defaultQuerySettings = $this->objectManager->get(Typo3QuerySettings::class);

        $defaultQuerySettings->setRespectStoragePage(false);
        $defaultQuerySettings->setRespectSysLanguage(false);

        $this->setDefaultQuerySettings($defaultQuerySettings);
    }
}
