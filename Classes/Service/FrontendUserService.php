<?php
declare(strict_types=1);
namespace Derhansen\FeChangePwd\Service;

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;
use TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility;

/**
 * Class FrontendUserService
 */
class FrontendUserService
{
    /**
     * The session key
     */
    const SESSION_KEY = 'mustChangePasswordReason';

    /**
     * @var SettingsService
     */
    protected $settingsService = null;

    /**
     * @param SettingsService $settingsService
     */
    public function injectSettingsService(\Derhansen\FeChangePwd\Service\SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Returns if the frontend user must change the password
     *
     * @param array $feUserRecord
     * @return bool
     */
    public function mustChangePassword(array $feUserRecord)
    {
        $reason = '';
        $result = false;
        $mustChangePassword = $feUserRecord['must_change_password'] ?? 0;
        $passwordExpiryTimestamp = $feUserRecord['password_expiry_date'] ?? 0;
        if ((bool)$mustChangePassword) {
            $reason = 'forcedChange';
            $result = true;
        } elseif (((int)$passwordExpiryTimestamp > 0 && (int)$passwordExpiryTimestamp < time())) {
            $reason = 'passwordExpired';
            $result = true;
        }

        if ($result) {
            // Store reason for password change in user session
            $this->getFrontendUser()->setKey('ses', self::SESSION_KEY, $reason);
            $this->getFrontendUser()->storeSessionData();
        }
        return $result;
    }

    /**
     * Returns the reason for the password change stored in the session
     *
     * @return mixed
     */
    public function getMustChangePasswordReason()
    {
        return $this->getFrontendUser()->getKey('ses', self::SESSION_KEY);
    }

    /**
     * Updates the password of the current user if a current user session exist
     *
     * @param string $newPassword
     * @return void
     */
    public function updatePassword(string $newPassword)
    {
        if (!$this->isUserLoggedIn()) {
            return;
        }

        $password = $this->getPasswordHash($newPassword);

        $userTable = $this->getFrontendUser()->user_table;
        $userUid = $this->getFrontendUser()->user['uid'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($userTable);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->update($userTable)
            ->set('password', $password)
            ->set('must_change_password', 0)
            ->set('password_expiry_date', $this->settingsService->getPasswordExpiryTimestamp())
            ->set('tstamp', (int)$GLOBALS['EXEC_TIME'])
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($userUid, \PDO::PARAM_INT)
                )
            )
            ->execute();

        // Unset reason for password change in user session
        $this->getFrontendUser()->setKey('ses', self::SESSION_KEY, null);
    }

    /**
     * Returns a password hash
     *
     * @param string $password
     * @return string
     * @throws MissingPasswordHashServiceException
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     */
    protected function getPasswordHash(string $password)
    {
        if (class_exists(PasswordHashFactory::class)) {
            $hashInstance = GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance('FE');
            $password = $hashInstance->getHashedPassword($password);
        } elseif (SaltedPasswordsUtility::isUsageEnabled('FE')) {
            $saltingInstance = SaltFactory::getSaltingInstance();
            $password = $saltingInstance->getHashedPassword($password);
        } else {
            throw new MissingPasswordHashServiceException(
                'No secure password hashing service could be initialized. Please check your TYPO3 system configuration',
                1557550040515
            );
        }

        return $password;
    }

    /**
     * Returns is there is a current user login
     *
     * @return bool
     */
    public function isUserLoggedIn()
    {
        return  $GLOBALS['TSFE']->loginUser;
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
