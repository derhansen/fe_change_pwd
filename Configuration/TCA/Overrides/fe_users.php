<?php
defined('TYPO3_MODE') || die();

$tmp_columns = [
    'must_change_password' => [
        'exclude' => true,
        'label' => 'LLL:EXT:fe_change_pwd/Resources/Private/Language/locallang_be.xlf:label.must_change_password',
        'config' => [
            'type' => 'check',
        ]
    ],
    'password_expiry_date' => [
        'exclude' => true,
        'label' => 'LLL:EXT:fe_change_pwd/Resources/Private/Language/locallang_be.xlf:label.password_expiry_date',
        'config' => [
            'type' => 'input',
            'renderType' => 'inputDateTime',
            'eval' => 'datetime',
        ]
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $tmp_columns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    'must_change_password, password_expiry_date',
    '',
    'after:password'
);
