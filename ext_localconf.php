<?php

defined('TYPO3') or die();

use Derhansen\FeChangePwd\Controller\PasswordController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionUtility::configurePlugin(
    'fe_change_pwd',
    'Pi1',
    [
        PasswordController::class => ['edit', 'update', 'sendChangePasswordCode'],
    ],
    // non-cacheable actions
    [
        PasswordController::class => ['edit', 'update', 'sendChangePasswordCode'],
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

// Define template override for Fluid email
if (!isset($GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][750])) {
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][750] = 'EXT:fe_change_pwd/Resources/Private/Templates/Email';
}
