<?php
namespace Derhansen\FeChangePwd\Service;

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class LocalizationService - wrapper class for LocalizationUtility so calls can be mocked in tests
 */
class LocalizationService
{
    /**
     * Translates the given key with the given argumens
     *
     * @param string $key
     * @param array $arguments
     * @return string
     */
    public function translate($key, $arguments = [])
    {
        $result = LocalizationUtility::translate($key, 'fe_change_pwd', $arguments);
        if (!$result) {
            $result = '';
        }
        return $result;
    }
}
