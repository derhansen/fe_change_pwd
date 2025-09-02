<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

/**
 * Plugins
 */
ExtensionUtility::registerPlugin(
    'fe_change_pwd',
    'Pi1',
    'LLL:EXT:fe_change_pwd/Resources/Private/Language/locallang_be.xlf:plugin.title',
    'ext-fechangepwd-default',
    'plugins',
    'LLL:EXT:fe_change_pwd/Resources/Private/Language/locallang_be.xlf:plugin.description',
);

/**
 * Default TypoScript
 */
ExtensionManagementUtility::addStaticFile(
    'fe_change_pwd',
    'Configuration/TypoScript',
    'Change password for frontend users'
);
