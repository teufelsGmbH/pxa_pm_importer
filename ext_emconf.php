<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Importer for product manager',
    'description' => 'Allow to create and list products on a site.',
    'category' => 'plugin',
    'author' => 'Andriy Oprysko',
    'author_email' => 'andriy.oprysko@resultify.se',
    'state' => 'alpha',
    'clearCacheOnLoad' => true,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7-9.4.99'
        ],
        'conflicts' => [],
        'suggests' => []
    ]
];
