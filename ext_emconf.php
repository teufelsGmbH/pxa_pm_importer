<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Importer for product manager',
    'description' => 'Importer boilerplate extension for pxa_product_manager.',
    'category' => 'plugin',
    'author' => 'Andriy Oprysko',
    'author_email' => 'andriy.oprysko@resultify.se',
    'state' => 'beta',
    'clearCacheOnLoad' => true,
    'version' => '1.1.1',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7-9.4.99',
            'pxa_product_manager' => ''
        ],
        'conflicts' => [],
        'suggests' => []
    ]
];
