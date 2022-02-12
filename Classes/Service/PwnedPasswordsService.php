<?php

declare(strict_types=1);

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Derhansen\FeChangePwd\Service;

use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PwnedPasswordsService
 */
class PwnedPasswordsService
{
    const API_URL = 'https://api.pwnedpasswords.com/range/';

    /**
     * Checks the given password against data breaches using the haveibeenpwned.com API
     * Returns the amount of times the password is found in the haveibeenpwned.com database
     *
     * @param string $password
     * @return int
     */
    public function checkPassword(string $password): int
    {
        $hash = sha1($password);
        $request = GeneralUtility::makeInstance(RequestFactory::class);
        $response = $request->request(
            self::API_URL . substr($hash, 0, 5),
            'GET',
            [
                'User-Agent' => 'TYPO3 Extension fe_change_pwd',
            ]
        );

        $results = $response->getBody()->getContents();
        if (($response->getStatusCode() !== 200) || empty($results)) {
            // Something went wrong with the request, return 0 and ignore check
            return 0;
        }

        if (preg_match('/' . preg_quote(substr($hash, 5)) . ':([0-9]+)/ism', $results, $matches) === 1) {
            return (int)$matches[1];
        }
        return 0;
    }
}
