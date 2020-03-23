<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Importer for product manager',
    'description' => 'Importer boilerplate extension for pxa_product_manager.',
    'category' => 'plugin',
    'author' => 'Andriy Oprysko',
    'author_email' => 'andriy.oprysko@resultify.se',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '2.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-9.5.99',
            'pxa_product_manager' => '9.5.1-9.99.99'
        ],
        'conflicts' => [],
        'suggests' => []
    ]
];
