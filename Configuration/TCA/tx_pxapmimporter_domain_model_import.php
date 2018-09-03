<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:pxa_pm_importer/Resources/Private/Language/locallang_db.xlf:tx_pxapmimporter_domain_model_import',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => false,
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'rootLevel' => true,
        'searchFields' => 'name,configuration_path',
        'iconfile' => 'EXT:pxa_pm_importer/Resources/Public/Icons/import.svg'
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden, name, configuration_path',
    ],
    'types' => [
        '1' => ['showitem' => 'hidden, name, configuration_path'],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
                    ]
                ],
            ],
        ],
        'last_execution' => [
            'exclude' => true,
            'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:pxa_pm_importer/Resources/Private/Language/locallang_db.xlf:tx_pxapmimporter_domain_model_import.last_execution',
            'config' => [
                'type' => 'input',
                'size' => 13,
                'eval' => 'datetime',
                'default' => 0,
            ],
        ],
        'name' => [
            'exclude' => true,
            'label' => 'LLL:EXT:pxa_pm_importer/Resources/Private/Language/locallang_db.xlf:tx_pxapmimporter_domain_model_import.name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'configuration_path' => [
            'exclude' => true,
            'label' => 'LLL:EXT:pxa_pm_importer/Resources/Private/Language/locallang_db.xlf:tx_pxapmimporter_domain_model_import.configuration_path',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:pxa_pm_importer/Resources/Private/Language/locallang_db.xlf:tx_pxapmimporter_domain_model_import.configuration_path.select', 0],
                ],
                'itemsProcFunc' => \Pixelant\PxaPmImporter\UserFunction\ConfigurationPathSelect::class . '->renderItems',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 1,
                'eval' => 'required'
            ],
        ],
        'crdate' => [
            'config' => [
                'type' => 'passthrough',
            ]
        ]
    ],
];
