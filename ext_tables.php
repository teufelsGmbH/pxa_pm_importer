<?php
defined('TYPO3_MODE') || die('Access denied.');

if (TYPO3_MODE === 'BE') {
    // Register BE module
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Pixelant.pxa_pm_importer',
        'tools',          // Main area
        'import',         // Name of the module
        '',             // Position of the module
        [
            'ImportModule' => 'index, import'
        ],
        [          // Additional configuration
            'access' => 'user,group',
            'icon' => 'EXT:pxa_pm_importer/ext_icon.svg',
            'labels' => 'LLL:EXT:pxa_pm_importer/Resources/Private/Language/locallang_mod.xlf',
        ]
    );
}
