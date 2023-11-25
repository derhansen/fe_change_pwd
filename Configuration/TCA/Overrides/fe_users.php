<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$tmp_columns = [
    'must_change_password' => [
        'exclude' => true,
        'label' => 'LLL:EXT:fe_change_pwd/Resources/Private/Language/locallang_be.xlf:label.must_change_password',
        'config' => [
            'type' => 'check',
        ],
    ],
    'password_expiry_date' => [
        'exclude' => true,
        'label' => 'LLL:EXT:fe_change_pwd/Resources/Private/Language/locallang_be.xlf:label.password_expiry_date',
        'config' => [
            'type' => 'datetime',
            'default' => 0,
        ],
    ],
];

ExtensionManagementUtility::addTCAcolumns('fe_users', $tmp_columns);
ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    '--palette--;LLL:EXT:fe_change_pwd/Resources/Private/Language/locallang_be.xlf:label.password_settings;password_settings',
    '',
    'after:password'
);

// Add the new palette:
$GLOBALS['TCA']['fe_users']['palettes']['password_settings'] = [
    'showitem' => 'must_change_password, password_expiry_date',
];
