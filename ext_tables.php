<?php
defined('TYPO3_MODE') || die('Access denied.');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
    'tx_pxapmimporter_domain_model_import',
    'EXT:pxa_pm_importer/Resources/Private/Language/locallang_csh_tx_pxapmimporter_domain_model_import.xlf'
);

if (TYPO3_MODE === 'BE') {
    // Register BE module
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Pixelant.' . $_EXTKEY,
        'tools',          // Main area
        'import',         // Name of the module
        '',             // Position of the module
        [
            'ImportModule' => 'index, import'
        ],
        [          // Additional configuration
            'access' => 'user,group',
            'icon' => 'EXT:' . $_EXTKEY . '/ext_icon.svg',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xml',
        ]
    );
}