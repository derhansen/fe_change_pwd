<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Change password for frontend users',
    'description' => 'Plugin to enable password change for frontend users. Contains configurable password 
        rules and password change enforcement.',
    'category' => 'plugin',
    'author' => 'Torben Hansen',
    'author_email' => 'torben@derhansen.com',
    'state' => 'stable',
    'version' => '3.2.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.5.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
