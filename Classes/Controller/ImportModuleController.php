<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Controller;

use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Domain\Repository\ImportRepository;
use Pixelant\PxaPmImporter\Exception\InvalidConfigurationException;
use Pixelant\PxaPmImporter\Service\ImportManager;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class ImportModuleController
 * @package Pixelant\PxaPmImporter\Controller
 */
class ImportModuleController extends ActionController
{
    /**
     * BackendTemplateContainer
     *
     * @var BackendTemplateView
     */
    protected $view = null;

    /**
     * Backend Template Container
     *
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

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
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);
    }

    /**
     * Main view
     */
    public function indexAction()
    {
        $this->view->assign('configurations', $this->importRepository->findAll());
    }

    /**
     * Import single configuration
     *
     * @param Import $import
     */
    public function importAction(Import $import = null)
    {
        $importManager = GeneralUtility::makeInstance(ImportManager::class, $this->importRepository);

        try {
            if ($import === null) {
                // @codingStandardsIgnoreStart
                throw new InvalidConfigurationException('Could not find configuration', 1535965019611);
                // @codingStandardsIgnoreEnd
            }

            $importManager->execute($import);

            die('executed');
            $this->addFlashMessage(
                $this->translate('be.executed'),
                $this->translate('be.success'),
                FlashMessage::OK
            );
        } catch (\Exception $exception) {
            $this->addFlashMessage(
                $this->translate('be.failed_execution', [$exception->getMessage()]),
                $this->translate('be.error'),
                FlashMessage::ERROR
            );
        }

        $this->redirect('index');
    }

    /**
     * Translate key
     *
     * @param string $key
     * @param array $arguments
     * @return string
     */
    protected function translate(string $key, array $arguments = null): string
    {
        return LocalizationUtility::translate($key, 'PxaPmImporter', $arguments) ?? '';
    }
}
