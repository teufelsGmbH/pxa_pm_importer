<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Command;

use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Domain\Repository\ImportRepository;
use Pixelant\PxaPmImporter\Service\ImportManager;
use Pixelant\PxaPmImporter\Traits\TranslateBeTrait;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Class ImportCommandController
 * @package Pixelant\PxaPm\Importer\Command
 */
class ImportCommandController extends CommandController
{
    use TranslateBeTrait;

    /**
     * @var ImportRepository
     */
    protected $importRepository = null;

    /**
     * @var PersistenceManager
     */
    protected $persistenceManager = null;

    /**
     * @param ImportRepository $importRepository
     */
    public function injectImportRepository(ImportRepository $importRepository): void
    {
        $this->importRepository = $importRepository;
    }

    /**
     * @param PersistenceManager $persistenceManager
     */
    public function injectPersistenceManager(PersistenceManager $persistenceManager): void
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Import main task
     *
     * @param string $email
     */
    public function importCommand(string $email = ''): void
    {
        /** @var Import[] $imports */
        $imports = $this->importRepository->findAll();

        $importManager = GeneralUtility::makeInstance(
            ImportManager::class,
            $this->persistenceManager,
            $this->importRepository
        );

        foreach ($imports as $import) {
            try {
                // Run import
                $importManager->runScheduled($import);
            } catch (\Exception $exception) {
                if (GeneralUtility::isValidUrl($email)) {
                    $mailMessage = GeneralUtility::makeInstance(MailMessage::class);

                    $mailMessage
                        ->setTo([$email])
                        ->setSubject($this->translate('be.mail.error_subject'))
                        ->setBody(
                            $this->translate(
                                'be.import_error_occurred',
                                [$import->getName(), $exception->getMessage()]
                            ),
                            'text/plain'
                        );

                    $mailMessage->send();
                } else {
                    throw $exception;
                }
            }
        }
    }
}
