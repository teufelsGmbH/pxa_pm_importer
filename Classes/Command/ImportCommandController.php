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
     * Error emails
     *
     * @var array
     */
    protected $emails = [];

    /**
     * @var string
     */
    protected $receiversEmails = '';

    /**
     * @var string
     */
    protected $senderEmail = '';

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
        $this->setReceiversEmails($receiversEmails);
        $this->setSenderEmail($senderEmail);

        $this->emitSignal('beforeSchedulerImportStart', [$importUids, $receiversEmails, $senderEmail]);

        foreach (GeneralUtility::intExplode(',', $importUids, true) as $importUid) {
            $this->import($importUid);
        }

        $this->emitSignal('afterSchedulerImportDone', [$importUids, $receiversEmails, $senderEmail]);

        $this->sendEmails();
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
     * @throws \Exception
     */
    protected function import(int $importUid): void
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
                $errors = $importManager->getErrors();
            }
        } catch (\Exception $exception) {
            $errors = [$exception->getMessage()];
        }

        if (isset($errors)) {
            $importName = is_object($import)
                ? $this->translate('be.import_name', [$import->getName() . ' (UID - ' . $import->getUid() . ')'])
                : '';
            $message = sprintf(
                '%s<br>%s<br><br>%s<br>%s',
                $this->translate('be.import_error_occurred'),
                $importName,
                $this->translate('be.error_message'),
                implode('<br>', $errors)
            );

            $this->registerMailMessage($importManager->getLogFilePath(), $message);
        }

        if (isset($exception)) {
            $this->sendEmails();

            throw $exception;
        }
    }

    /**
     * Save mail message
     *
     * @param string $logPath
     * @param string $message
     */
    protected function registerMailMessage(string $logPath, string $message): void
    {
        if (!array_key_exists($logPath, $this->emails)) {
            $this->emails[$logPath] = [];
        }

        $this->emails[$logPath][] = $message;
    }

    /**
     * Send error emails
     */
    protected function sendEmails(): void
    {
        if (empty($this->receiversEmails) || empty($this->senderEmail)) {
            return;
        }

        foreach ($this->emails as $logPath => $messages) {
            $message = implode('<br><br>', $messages);

            if (!empty($message)) {
                $message = sprintf(
                    '%s<br><br>%s<br>%s',
                    $message,
                    $this->translate('be.see_log'),
                    $logPath
                );

                $this->sendEmail(
                    $this->senderEmail,
                    $this->translate('be.mail.error_subject'),
                    $message,
                    ...GeneralUtility::trimExplode(',', $this->receiversEmails)
                );
            }
        }
    }

    /**
     * @param string $receiversEmails
     */
    protected function setReceiversEmails(string $receiversEmails): void
    {
        $this->receiversEmails = $receiversEmails;
    }

    /**
     * @param string $senderEmail
     */
    protected function setSenderEmail(string $senderEmail): void
    {
        $this->senderEmail = $senderEmail;
    }
}
