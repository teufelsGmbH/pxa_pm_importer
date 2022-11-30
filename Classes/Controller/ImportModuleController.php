<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Controller;

use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use Pixelant\PxaPmImporter\Service\ImportService;
use Pixelant\PxaPmImporter\Utility\ImportersRegistry;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
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
     * @var PageRenderer
     */
    protected $pageRenderer = null;
    private ModuleTemplateFactory $moduleTemplateFactory;
    public function __construct(ModuleTemplateFactory $moduleTemplateFactory)
    {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * @param PageRenderer $pageRenderer
     */
    public function injectPageRenderer(PageRenderer $pageRenderer)
    {
        $this->pageRenderer = $pageRenderer;
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
        $this->pageRenderer->loadRequireJsModule(
            'TYPO3/CMS/PxaPmImporter/Backend/ImportModule',
            'function(ImportModule) { (new ImportModule).init(); }'
        );
    }

    /**
     * Main view
     */
    public function indexAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $configurations = ImportersRegistry::getImportersAvailableConfigurations();

        $this->view->assignMultiple(compact('configurations'));
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Import single configuration
     *
     * @param string $configuration Import configuration
     */
    public function importAction(string $configuration)
    {
        $importManager = $this->objectManager->get(ImportService::class);

        try {
            $importManager->execute($configuration);

            $this->addFlashMessage(
                $this->translate('be.executed', [$importManager->getLogFilePath()]),
                $this->translate('be.success'),
                AbstractMessage::OK
            );

            foreach ($importManager->getErrors() as $error) {
                $this->addFlashMessage(
                    $error,
                    $this->translate('be.error'),
                    AbstractMessage::ERROR
                );
            }
        } catch (\Exception $exception) {
            $this->addFlashMessage(
                $this->translate('be.failed_execution', [$exception->getMessage()]),
                $this->translate('be.error'),
                AbstractMessage::ERROR
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
