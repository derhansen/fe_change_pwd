<?php

declare(strict_types=1);

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Derhansen\FeChangePwd\Service;

use Derhansen\FeChangePwd\Exception\InvalidUserException;
use Derhansen\FeChangePwd\Exception\MissingPasswordHashServiceException;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Service class with frontend user helper functions
 */
class FrontendUserService
{
    public const SESSION_KEY = 'mustChangePasswordReason';

    protected SettingsService $settingsService;

    public function injectSettingsService(SettingsService $settingsService): void
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Returns if the frontend user must change the password
     */
    public function mustChangePassword(array $feUserRecord): bool
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
     */
    public function getMustChangePasswordReason(): string
    {
        return (string)$this->getFrontendUser()->getKey('ses', self::SESSION_KEY);
    }

    /**
     * Updates the password of the current user if a current user session exist
     */
    public function updatePassword(string $newPassword, array $settings): void
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
            ->set('password_expiry_date', $this->settingsService->getPasswordExpiryTimestamp($settings))
            ->set('tstamp', (int)$GLOBALS['EXEC_TIME'])
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($userUid, \PDO::PARAM_INT)
                )
            )
            ->executeQuery();

        // Unset reason for password change in user session
        $this->getFrontendUser()->setKey('ses', self::SESSION_KEY, null);

        // Destroy all sessions of the user except the current one
        $sessionManager = GeneralUtility::makeInstance(SessionManager::class);
        $sessionBackend = $sessionManager->getSessionBackend('FE');
        $sessionManager->invalidateAllSessionsByUserId(
            $sessionBackend,
            (int)$this->getFrontendUser()->user['uid'],
            $this->getFrontendUser()
        );
    }

    /**
     * Returns the changeHmac for the current logged in user
     */
    public function getChangeHmac(): string
    {
        if (!$this->isUserLoggedIn()) {
            return '';
        }

        $userUid = $this->getFrontendUser()->user['uid'];
        if (!is_int($userUid) || (int)$userUid <= 0) {
            throw new InvalidUserException('The fe_user uid is not a positive number.', 1574102778917);
        }

        $tstamp = $this->getFrontendUser()->user['tstamp'];
        return GeneralUtility::hmac('fe_user_' . $userUid . '_' . $tstamp, 'fe_change_pwd');
    }

    /**
     * Validates the given changeHmac
     */
    public function validateChangeHmac(string $changeHmac): bool
    {
        return $changeHmac !== '' && hash_equals($this->getChangeHmac(), $changeHmac);
    }

    /**
     * Returns a password hash
     */
    protected function getPasswordHash(string $password): string
    {
        if (class_exists(PasswordHashFactory::class)) {
            $hashInstance = GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance('FE');
            $password = $hashInstance->getHashedPassword($password);
        } else {
            throw new MissingPasswordHashServiceException(
                'No secure password hashing service could be initialized. Please check your TYPO3 system configuration',
                1557550040
            );
        }

        return $password;
    }

    /**
     * Returns is there is a current user login
     */
    public function isUserLoggedIn(): bool
    {
        return GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user')->isLoggedIn();
    }

    /**
     * Returns the frontendUserAuthentication
     */
    protected function getFrontendUser(): FrontendUserAuthentication
    {
        return $GLOBALS['TSFE']->fe_user;
    }
}
