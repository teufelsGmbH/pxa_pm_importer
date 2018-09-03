<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Command;

use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Domain\Repository\ImportRepository;
use Pixelant\PxaPmImporter\Exception\InvalidConfigurationException;
use Pixelant\PxaPmImporter\Service\ImportManager;
use Pixelant\PxaPmImporter\Traits\EmitSignalTrait;
use Pixelant\PxaPmImporter\Traits\TranslateBeTrait;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class ImportCommandController
 * @package Pixelant\PxaPm\Importer\Command
 */
class ImportCommandController extends CommandController
{
    use TranslateBeTrait;
    use EmitSignalTrait;

    /**
     * @var ImportRepository
     */
    protected $importRepository = null;

    /**
     * @param ImportRepository $importRepository
     */
    public function injectImportRepository(ImportRepository $importRepository): void
    {
        $this->importRepository = $importRepository;
    }

    /**
     * Import main task
     *
     * @param int $importUid Import configuration uid
     * @param string $email Notify about import errors
     */
    public function importCommand(int $importUid, string $email = ''): void
    {
        try {
            /** @var Import $import */
            $import = $this->importRepository->findByUid($importUid);

            if ($import === null) {
                // @codingStandardsIgnoreStart
                throw new InvalidConfigurationException('Could not find configuration with UID "' . $importUid . '"', 1535957269248);
                // @codingStandardsIgnoreEnd
            }

            $importManager = GeneralUtility::makeInstance(
                ImportManager::class,
                $this->importRepository
            );

            $this->emitSignal('beforeImportExecution', [$import]);
            // Run import
            $importManager->execute($import);

            $this->emitSignal('afterImportExecution', [$import]);
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
