<?php
defined('TYPO3') || die;

// This table is invisible, no sense to put labels in XLF file
return [
    'ctrl' => [
        'title' => 'Progress bar',
        'label' => 'configuration',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'searchFields' => 'configuration',
        'rootLevel' => 1,
        'hideTable' => true,
    ],
    'types' => [
        '1' => ['showitem' => '--palette--;;1, configuration, progress'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
    'columns' => [
        'configuration' => [
            'exclude' => false,
            'label' => 'Configuration path',
            'config' => [
                'type' => 'input',
                'eval' => 'trim,required'
            ],
        ],
        'progress' => [
            'exclude' => false,
            'label' => 'Progress of import',
            'config' => [
                'type' => 'input',
                'eval' => 'double2'
            ],
        ],
    ],
];
