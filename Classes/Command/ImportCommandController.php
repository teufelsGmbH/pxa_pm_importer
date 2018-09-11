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
     * @param string $senderEmail Sender email
     */
    public function importCommand(int $importUid, string $email = '', string $senderEmail = ''): void
    {
        try {
            $importManager = GeneralUtility::makeInstance(
                ImportManager::class,
                $this->importRepository
            );

            /** @var Import $import */
            $import = $this->importRepository->findByUid($importUid);

            if ($import === null) {
                // @codingStandardsIgnoreStart
                throw new InvalidConfigurationException('Could not find configuration with UID "' . $importUid . '"', 1535957269248);
                // @codingStandardsIgnoreEnd
            }

            // Run import
            $importManager->execute($import);

            if (!empty($importManager->getErrors())) {
                if (GeneralUtility::validEmail($email)) {
                    $body = array_merge(
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

                    $this->sendEmail($email, $senderEmail, $body);
                }
            }
        } catch (\Exception $exception) {
            if (GeneralUtility::validEmail($email)) {
                $body = [
                    $this->translate('be.import_error_occurred'),
                    $this->translate('be.import_name', [$import->getName() . ' (UID - ' . $import->getUid() . ')']),
                    '<br />',
                    $this->translate('be.error_message'),
                    $exception->getMessage(),
                    '<br />',
                    $this->translate('be.see_log'),
                    '"' . $importManager->getLogFilePath() . '"'
                ];

                $this->sendEmail($email, $senderEmail, $body);
            }

            throw $exception;
        }
    }

    /**
     * Send email
     *
     * @param string $receiver
     * @param string $sender
     * @param array $body
     */
    protected function sendEmail(string $receiver, string $sender, array $body): void
    {
        $mailMessage = GeneralUtility::makeInstance(MailMessage::class);
        $mailMessage
            ->setTo([$receiver])
            ->setSubject($this->translate('be.mail.error_subject'))
            ->setBody(
                implode('<br />', $body),
                'text/html'
            );
        if ($sender !== '') {
            $mailMessage->setFrom($sender);
        }

        $mailMessage->send();
    }
}
