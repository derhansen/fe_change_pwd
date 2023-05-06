<?php

declare(strict_types=1);

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Derhansen\FeChangePwd\Service;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Wrapper class for LocalizationUtility so calls can be mocked in tests
 */
class LocalizationService
{
    /**
     * Translates the given key with the given arguments
     */
    public function translate(string $key, array $arguments = []): string
    {
        $result = LocalizationUtility::translate($key, 'FeChangePwd', $arguments);
        if (!$result) {
            $result = '';
        }
        return $result;
    }
}
