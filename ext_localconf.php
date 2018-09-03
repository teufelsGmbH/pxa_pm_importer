<?php
defined('TYPO3_MODE') || die('Access denied.');


if (TYPO3_MODE === 'BE') {
    // Register task
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']['PxaPmImporter'] =
        \Pixelant\PxaPmImporter\Command\ImportCommandController::class;

    // Register logger
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['Pixelant']['PxaPmImporter']['writerConfiguration'])) {
        $context = \TYPO3\CMS\Core\Utility\GeneralUtility::getApplicationContext();
        $logFile = 'typo3temp/var/logs/pim_importer.log';

        if (!empty($logFile)) {
            if ($context->isProduction()) {
                $logLevel = \TYPO3\CMS\Core\Log\LogLevel::ERROR;
            } elseif ($context->isDevelopment()) {
                $logLevel = \TYPO3\CMS\Core\Log\LogLevel::DEBUG;
            } else {
                $logLevel = \TYPO3\CMS\Core\Log\LogLevel::INFO;
            }

            $GLOBALS['TYPO3_CONF_VARS']['LOG']['Pixelant']['PxaPmImporter']['writerConfiguration'] = [
                $logLevel => [
                    \Pixelant\PxaPmImporter\Logging\Writer\FileWriter::class => [
                        'logFile' => $logFile
                    ]
                ]
            ];
        }
    }

    // Register importer
    \Pixelant\PxaPmImporter\Utility\ImportersRegistry::registerImporter('pxa_pm_importer');
}
