<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Change password for frontend users',
    'description' => 'Plugin to enable password change for frontend users. Contains configurable password 
        rules and password change enforcement.',
    'category' => 'plugin',
    'author' => 'Torben Hansen',
    'author_email' => 'torben@derhansen.com',
    'state' => 'stable',
    'uploadfolder' => '0',
    'clearCacheOnLoad' => 0,
    'version' => '2.0.3',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-10.4.99',
            'php' => '7.2.0-7.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
