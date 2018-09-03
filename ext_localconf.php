<?php
defined('TYPO3_MODE') || die('Access denied.');



if (TYPO3_MODE === 'BE') {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']['PxaPmImporter'] =
        \Pixelant\PxaPmImporter\Command\ImportCommandController::class;

    // Register importer
    \Pixelant\PxaPmImporter\Utility\ImportersRegistry::registerImporter('pxa_pm_importer');
}
