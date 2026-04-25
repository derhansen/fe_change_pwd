<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Change password for frontend users',
    'description' => 'Plugin to enable password change for frontend users. Contains configurable password
        rules and password change enforcement.',
    'category' => 'plugin',
    'author' => 'Torben Hansen',
    'author_email' => 'torben@derhansen.com',
    'state' => 'stable',
    'version' => '6.0.2',
    'constraints' => [
        'depends' => [
            'typo3' => '14.3.0-14.3.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
