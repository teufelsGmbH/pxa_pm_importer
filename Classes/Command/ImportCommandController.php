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
     * @param string $importUids Import configuration uids list
     * @param string $receiversEmails Notify about import errors(comma list for multiple receivers)
     * @param string $senderEmail Sender email
     */
    public function importCommand(string $importUids, string $receiversEmails = '', string $senderEmail = ''): void
    {
        $this->emitSignal('beforeSchedulerImportStart', [$importUids, $receiversEmails, $senderEmail]);

        foreach (GeneralUtility::intExplode(',', $importUids, true) as $importUid) {
            $this->import($importUid, $receiversEmails, $senderEmail);
        }

        $this->emitSignal('afterSchedulerImportDone', [$importUids, $receiversEmails, $senderEmail]);
    }

    /**
     * Send email
     *
     * @param string $sender Sender email
     * @param string $subject Email subject
     * @param string $message Message
     * @param string[] $receivers Email receivers
     */
    protected function sendEmail(string $sender, string $subject, string $message, string ...$receivers): void
    {
        $mailMessage = GeneralUtility::makeInstance(MailMessage::class);
        $mailMessage
            ->setFrom($sender)
            ->setTo($receivers)
            ->setSubject($subject)
            ->setBody(
                $message,
                'text/html'
            );

        $mailMessage->send();
    }

    /**
     * Run single import
     *
     * @param int $importUid
     * @param string $receivers
     * @param string $senderEmail
     * @throws \Exception
     */
    protected function import(int $importUid, string $receivers, string $senderEmail): void
    {
        $importManager = GeneralUtility::makeInstance(
            ImportManager::class,
            $this->importRepository
        );

        /** @var Import $import */
        $import = $this->importRepository->findByUid($importUid);

        try {
            if ($import === null) {
                // @codingStandardsIgnoreStart
                throw new InvalidConfigurationException('Could not find configuration with UID "' . $importUid . '"', 1535957269248);
                // @codingStandardsIgnoreEnd
            }

            // Run import
            $importManager->execute($import);

            if (!empty($importManager->getErrors())) {
                $errorMessageParts = array_merge(
                    [
                        $this->translate('be.import_error_occurred'),
                        $this->translate(
                            'be.import_name',
                            [$import->getName() . ' (UID - ' . $import->getUid() . ')']
                        ),
                        '<br />',
                        $this->translate('be.error_message'),
                    ],
                    $importManager->getErrors(),
                    [
                        '<br />',
                        $this->translate('be.see_log'),
                        '"' . $importManager->getLogFilePath() . '"'
                    ]
                );
            }
        } catch (\Exception $exception) {
            $errorMessageParts = [
                $this->translate('be.import_error_occurred'),
                (is_object($import) ? $this->translate('be.import_name', [$import->getName() . ' (UID - ' . $import->getUid() . ')']) : ''),
                '<br />',
                $this->translate('be.error_message'),
                $exception->getMessage(),
                '<br />',
                $this->translate('be.see_log'),
                '"' . $importManager->getLogFilePath() . '"'
            ];
        }

        if (isset($errorMessageParts)
            && !empty($receivers)
            && !empty($senderEmail)
        ) {
            $this->sendEmail(
                $senderEmail,
                $this->translate('be.mail.error_subject'),
                implode('<br />', $errorMessageParts),
                ...GeneralUtility::trimExplode(',', $receivers)
            );
        }

        if (isset($exception)) {
            throw $exception;
        }
    }
}
