<?php

defined('TYPO3') or die();

call_user_func(function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Derhansen.fe_change_pwd',
        'Pi1',
        [
            \Derhansen\FeChangePwd\Controller\PasswordController::class => 'edit,update',
        ],
        // non-cacheable actions
        [
            \Derhansen\FeChangePwd\Controller\PasswordController::class => 'edit,update',
        ]
    );
});
