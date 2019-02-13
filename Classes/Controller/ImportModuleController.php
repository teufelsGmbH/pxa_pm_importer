<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Controller;

use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Domain\Repository\ImportRepository;
use Pixelant\PxaPmImporter\Exception\InvalidConfigurationException;
use Pixelant\PxaPmImporter\Service\ImportManager;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Registry;
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
     * Initialize index action stuff
     */
    public function initializeIndexAction()
    {
        $this->getPageRenderer()->loadRequireJsModule(
            'TYPO3/CMS/PxaPmImporter/Backend/ImportModule',
            'function(ImportModule) { (new ImportModule).init(); }'
        );
    }

    /**
     * Main view
     */
    public function indexAction()
    {
        $registry = $this->getRegistry();
        $lastImportInfo = $registry->get('tx_pxapmimporter', 'lastImport');
        if (is_array($lastImportInfo)) {
            $this->view
                ->assign('errors', $lastImportInfo['errors'])
                ->assign('logFile', $lastImportInfo['logFile']);

            $this->saveLastImportInformation(null); // Save with null
        }

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

            $this->addFlashMessage(
                $this->translate('be.executed'),
                $this->translate('be.success'),
                FlashMessage::OK
            );

            $this->saveLastImportInformation([
                'logFile' => $importManager->getLogFilePath(),
                'errors' => $importManager->getErrors()
            ]);
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
     * Save last import info
     * @param array $information
     */
    protected function saveLastImportInformation(?array $information): void
    {
        $this->getRegistry()->set('tx_pxapmimporter', 'lastImport', $information);
    }

    /**
     * @return Registry
     */
    protected function getRegistry(): Registry
    {
        return GeneralUtility::makeInstance(Registry::class);
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

    /**
     * Page renderer
     *
     * @return PageRenderer
     */
    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
