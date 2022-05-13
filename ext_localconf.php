<?php
defined('TYPO3_MODE') or die();

call_user_func(function () {
    $passwordControllerClass = version_compare(TYPO3_version, '10.0', '>=') ? \Derhansen\FeChangePwd\Controller\PasswordController::class : 'Password';
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        version_compare(TYPO3_version, '10.0', '>=') ? 'fe_change_pwd' : 'Derhansen.fe_change_pwd',
        'Pi1',
        [
            $passwordControllerClass => 'edit,update',
        ],
        // non-cacheable actions
        [
            $passwordControllerClass => 'edit,update',
        ]
    );
});
