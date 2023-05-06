<?php

return [
    'frontend' => [
        'derhansen/fe-change-pwd/force-password-change' => [
            'target' => \Derhansen\FeChangePwd\Middleware\ForcePasswordChangeRedirect::class,
            'description' => 'Force password change redirection',
            'after' => [
                'typo3/cms-frontend/page-resolver',
                'typo3/cms-core/response-propagation',
            ],
        ],
    ],
];
