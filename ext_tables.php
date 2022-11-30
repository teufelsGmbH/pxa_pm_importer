<?php
defined('TYPO3') || die('Access denied.');

if (TYPO3_MODE === 'BE') {
    // Register BE module
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'PxaPmImporter',
        'tools',          // Main area
        'import',         // Name of the module
        '',             // Position of the module
        [
            \Pixelant\PxaPmImporter\Controller\ImportModuleController::class => 'index, import'
        ],
        [          // Additional configuration
            'access' => 'user,group',
            'icon' => 'EXT:pxa_pm_importer/ext_icon.svg',
            'labels' => 'LLL:EXT:pxa_pm_importer/Resources/Private/Language/locallang_mod.xlf',
        ]
    );

    // Register importer
    // Example how to register extension
    //\Pixelant\PxaPmImporter\Utility\ImportersRegistry::registerImporter('pxa_pm_importer', ['Example/Yaml']);
}
