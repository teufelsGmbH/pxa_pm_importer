<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Importer for product manager',
    'description' => 'Importer boilerplate extension for pxa_product_manager.',
    'category' => 'plugin',
    'author' => 'Andriy Oprysko',
    'author_email' => 'andriy.oprysko@resultify.se',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '3.0.0-teufels',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-11.5.99',
            'pxa_product_manager' => '9.5.1-0.0.0'
        ],
        'conflicts' => [],
        'suggests' => []
    ]
];
