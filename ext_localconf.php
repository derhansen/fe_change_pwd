<?php

defined('TYPO3') or die();

call_user_func(function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'fe_change_pwd',
        'Pi1',
        [
            \Derhansen\FeChangePwd\Controller\PasswordController::class => 'edit,update,sendChangePasswordCode',
        ],
        // non-cacheable actions
        [
            \Derhansen\FeChangePwd\Controller\PasswordController::class => 'edit,update,sendChangePasswordCode',
        ]
    );

    // Define template override for Fluid email
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][750])) {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][750] = 'EXT:fe_change_pwd/Resources/Private/Templates/Email';
    }
});
