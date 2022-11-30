<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Command;

use Pixelant\PxaPmImporter\Service\ImportService;
use Pixelant\PxaPmImporter\Traits\EmitSignalTrait;
use Pixelant\PxaPmImporter\Traits\TranslateBeTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ImportCommandController
 * @package Pixelant\PxaPm\Importer\Command
 */
class ImportCommand extends Command
{
    use TranslateBeTrait;
    use EmitSignalTrait;

    /**
     * Error emails
     *
     * @var array
     */
    protected $emails = [];

    /**
     * @var string
     */
    protected $adminEmails = '';

    /**
     * @var string
     */
    protected $senderEmail = '';

    /**
     * @var ObjectManager
     */
    protected $objectManager = null;

    /**
     * @var ImportService
     */
    protected $importManager = null;

    /**
     * Configure
     */
    protected function configure()
    {
        $this
            ->setDescription('Import "pxa_product_manager" extension related records.')
            ->setHelp('This command import records using preconfigured YAML configuration...')
            ->addArgument(
                'configurations',
                InputArgument::REQUIRED,
                'Import YAML configurations (separate multiple configurations with a comma)'
            )
            ->addArgument(
                'adminEmails',
                InputArgument::OPTIONAL,
                'Admins emails (separate multiple emails with a comma)'
            )
            ->addArgument(
                'senderEmail',
                InputArgument::OPTIONAL,
                'Sender email'
            );
    }

    /**
     * Execute import
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set email options
        $this->adminEmails = $input->getArgument('adminEmails');
        $this->senderEmail = $input->getArgument('senderEmail');

        $this->initializeRequired();

        $importConfigurations = $input->getArgument('configurations');

        foreach (GeneralUtility::trimExplode(',', $importConfigurations) as $configuration) {
            $this->import($configuration);
        }

        $this->sendEmails();
    }

    /**
     * Required before start import
     */
    protected function initializeRequired()
    {
        // Make sure we can use datahandler
        Bootstrap::initializeBackendAuthentication();
        // Extbase
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->importManager = $this->objectManager->get(ImportService::class);
    }

    /**
     * Send email
     *
     * @param string $sender Sender email
     * @param string $subject Email subject
     * @param string $message Message
     * @param string[] ...$receivers Email receivers
     */
    protected function sendEmail(string $sender, string $subject, string $message, string ...$receivers): void
    {
        $mailMessage = GeneralUtility::makeInstance(MailMessage::class);
        $mailMessage
            ->setFrom($sender)
            ->setTo($receivers)
            ->setSubject($subject)
            ->html(
                $message
            );

        $mailMessage->send();
    }

    /**
     * Run single import
     *
     * @param string $configuration
     * @throws \Exception
     */
    protected function import(string $configuration): void
    {
        try {
            // Run import
            $this->importManager->execute($configuration);

            if (!empty($this->importManager->getErrors())) {
                $errors = $this->importManager->getErrors();
            }
        } catch (\Exception $exception) {
            $errors = [$exception->getMessage()];
        }

        if (isset($errors)) {
            $message = sprintf(
                '%s<br>%s<br><br>%s<br>%s',
                $this->translate('be.import_error_occurred'),
                $configuration,
                $this->translate('be.error_message'),
                implode('<br>', $errors)
            );

            $this->registerMailMessage($configuration, $message);
        }

        if (isset($exception)) {
            $this->sendEmails();

            throw $exception;
        }
    }

    /**
     * Save mail message
     *
     * @param string $configuration
     * @param string $message
     */
    protected function registerMailMessage(string $configuration, string $message): void
    {
        if (!array_key_exists($configuration, $this->emails)) {
            $this->emails[$configuration] = [];
        }

        $this->emails[$configuration][] = $message;
    }

    /**
     * Send error emails
     */
    protected function sendEmails(): void
    {
        if (empty($this->adminEmails) || empty($this->senderEmail)) {
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
                    ...GeneralUtility::trimExplode(',', $this->adminEmails)
                );
            }
        }
    }
}
