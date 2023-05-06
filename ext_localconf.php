<?php

defined('TYPO3') or die();

use Derhansen\FeChangePwd\Controller\PasswordController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

call_user_func(function () {
    ExtensionUtility::configurePlugin(
        'fe_change_pwd',
        'Pi1',
        [
            PasswordController::class => 'edit,update',
        ],
        // non-cacheable actions
        [
            PasswordController::class => 'edit,update',
        ]
    );
});
