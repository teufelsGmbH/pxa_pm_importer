<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {
    // Add TCA config of import fields for all tables where it's possible to import
    $columns = [
        'pm_importer_import_id' => [
            'config' => [
                'exclude' => false,
                'type' => 'input',
                'readOnly' => true
            ]
        ],
        'pm_importer_import_id_hash' => [
            'config' => [
                'exclude' => false,
                'type' => 'input',
                'readOnly' => true
            ]
        ]
    ];

    $importTables = [
        'tx_pxaproductmanager_domain_model_attribute',
        'tx_pxaproductmanager_domain_model_product',
        'tx_pxaproductmanager_domain_model_option',
        'sys_category'
    ];

    foreach ($importTables as $table) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, $columns);
    }
});
