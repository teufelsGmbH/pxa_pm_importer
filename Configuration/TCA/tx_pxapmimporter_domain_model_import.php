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
        'showRecordFieldList' => 'hidden, name, configuration_path, local_file_path',
    ],
    'types' => [
        '1' => ['showitem' => 'hidden, name, local_configuration, configuration_path, local_file_path'],
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
            'displayCond' => 'FIELD:local_configuration:REQ:false',
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
        'local_file_path' => [
            'exclude' => true,
            'label' => 'LLL:EXT:pxa_pm_importer/Resources/Private/Language/locallang_db.xlf:tx_pxapmimporter_domain_model_import.local_file_path',
            'displayCond' => 'FIELD:local_configuration:REQ:true',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'fieldControl' => [
                    'linkPopup' => [
                        'options' => [
                            'blindLinkOptions' => 'page,url,folder,mail,spec',
                            'blindLinkFields' => 'class,title,params,target',
                            'allowedExtensions' => 'yaml',
                        ]
                    ]
                ],
                'eval' => 'trim,required'
            ],
        ],
        'local_configuration' => [
            'exclude' => true,
            'onChange' => 'reload',
            'label' => 'LLL:EXT:pxa_pm_importer/Resources/Private/Language/locallang_db.xlf:tx_pxapmimporter_domain_model_import.local_configuration',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:pxa_pm_importer/Resources/Private/Language/locallang_db.xlf:tx_pxapmimporter_domain_model_import.local_configuration.yes', 1],
                ],
            ]
        ],
        'crdate' => [
            'config' => [
                'type' => 'passthrough',
            ]
        ]
    ],
];
