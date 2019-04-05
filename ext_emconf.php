<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Change password for frontend users',
    'description' => 'Plugin to enable password change for frontend users. Contains configurable password 
        rules and password change enforcement.',
    'category' => 'plugin',
    'author' => 'Torben Hansen',
    'author_email' => 'torben@derhansen.com',
    'shy' => '',
    'priority' => '',
    'module' => '',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => '0',
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 0,
    'lockType' => '',
    'version' => '1.2.4',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-9.5.99',
            'php' => '7.0.0-7.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'Derhansen\\FeChangePwd\\' => 'Classes'
        ]
    ],
];
