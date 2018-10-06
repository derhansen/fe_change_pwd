<?php

return [
    'frontend' => [
        'derhansen/fe-change-pwd/force-password-change' => [
            'target' => \Derhansen\FeChangePwd\Middleware\ForcePasswordChangeRedirect::class,
            'description' => 'Force password change redirection',
            'after' => [
                'typo3/cms-frontend/tsfe',
                'typo3/cms-frontend/authentication',
            ],
        ]
    ]
];
