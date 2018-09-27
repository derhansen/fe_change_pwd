<?php
defined('TYPO3_MODE') or die();

call_user_func(function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Derhansen.fe_change_pwd',
        'Pi1',
        [
            'Password' => 'edit,update',
        ],
        // non-cacheable actions
        [
            'Password' => 'edit,update',
        ]
    );

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']['fe_change_pwd'] =
        Derhansen\FeChangePwd\Hooks\TsfeHooks::class . '->checkForcePasswordChange';
});
