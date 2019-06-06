<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {
    // Register logger
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['Pixelant']['PxaPmImporter']['writerConfiguration'])) {
        if (version_compare(TYPO3_version, '9.0', '<')) {
            $logFile = 'typo3temp/var/logs/pm_importer.log';
        } else {
            $logFile = \TYPO3\CMS\Core\Core\Environment::getVarPath() . '/log/pm_importer.log';
        }

        $GLOBALS['TYPO3_CONF_VARS']['LOG']['Pixelant']['PxaPmImporter']['writerConfiguration'] = [
            \TYPO3\CMS\Core\Log\LogLevel::INFO => [
                \Pixelant\PxaPmImporter\Logging\Writer\FileWriter::class => [
                    'logFile' => $logFile
                ]
            ]
        ];
    }

    if (TYPO3_MODE === 'BE') {
        // Register task
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']['PxaPmImporter'] =
            \Pixelant\PxaPmImporter\Command\ImportCommandController::class;

        // Register importer
        // Example how to register extension
        //\Pixelant\PxaPmImporter\Utility\ImportersRegistry::registerImporter('pxa_pm_importer', ['Example/Yaml']);
    }
});
