<?php
declare(strict_types=1);
namespace Derhansen\FeChangePwd\Service;

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Derhansen\FeChangePwd\Exception\MissingPasswordHashServiceException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;

/**
 * Class OldPasswordsService
 */
class OldPasswordService
{
    /**
     * Returns if the given password equals the old password
     *
     * @param string $password
     * @return bool
     * @throws MissingPasswordHashServiceException
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     */
    public function checkEqualsOldPassword(string $password)
    {
        if (class_exists(PasswordHashFactory::class)) {
            $hashInstance = GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance('FE');
            $equals = $hashInstance->checkPassword($password, $this->getFrontendUser()->user['password']);
        } else {
            throw new MissingPasswordHashServiceException(
                'No secure password hashing service could be initialized. Please check your TYPO3 system configuration',
                1557550040515
            );
        }

        return $equals;
    }

    /**
     * Returns the frontendUserAuthentication
     *
     * @return \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication
     */
    protected function getFrontendUser()
    {
        return $GLOBALS['TSFE']->fe_user;
    }
}
