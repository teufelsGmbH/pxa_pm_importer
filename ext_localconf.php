<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {
    // Register logger
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['Pixelant']['PxaPmImporter']['writerConfiguration'])) {
        $logFile = \TYPO3\CMS\Core\Core\Environment::getVarPath() . '/log/pm_importer.log';

        $GLOBALS['TYPO3_CONF_VARS']['LOG']['Pixelant']['PxaPmImporter']['writerConfiguration'] = [
            \TYPO3\CMS\Core\Log\LogLevel::INFO => [
                \Pixelant\PxaPmImporter\Logging\Writer\FileWriter::class => [
                    'logFile' => $logFile
                ]
            ]
        ];
    }
});
