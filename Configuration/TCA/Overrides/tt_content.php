<?php
defined('TYPO3_MODE') or die();

/**
 * Plugins
 */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'fe_change_pwd',
    'Pi1',
    'LLL:EXT:fe_change_pwd/Resources/Private/Language/locallang_be.xlf:plugin.title'
);

/**
 * Remove unused fields
 */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['fechangepwd_pi1'] =
    'layout,recursive,select_key,pages';

/**
 * Default TypoScript
 */
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'fe_change_pwd',
    'Configuration/TypoScript',
    'Change password for frontend users'
);
