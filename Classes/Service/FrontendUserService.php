<?php
declare(strict_types=1);
namespace Derhansen\FeChangePwd\Service;

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher = null;

    /**
     * @param SettingsService $settingsService
     */
    public function injectSettingsService(\Derhansen\FeChangePwd\Service\SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * @param \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
     */
    public function injectSignalSlotDispatcher(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * Returns if the frontend user must change the password
     *
     * @param array $feUserRecord
     * @return bool
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

        $this->signalSlotDispatcher->dispatch(
            __CLASS__,
            __FUNCTION__ . 'AfterUpdate',
            [
                $this->getFrontendUser()->user,
                $this
            ]
        );

        // Unset reason for password change in user session
        $this->getFrontendUser()->setKey('ses', self::SESSION_KEY, null);
    }

    /**
     * Returns the changeHmac for the current logged in user
     *
     * @return string
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
     *
     * @param string $changeHmac
     * @return bool
     */
    public function validateChangeHmac(string $changeHmac): bool
    {
        return is_string($changeHmac) && $changeHmac !== '' && hash_equals($this->getChangeHmac(), $changeHmac);
    }

    /**
     * Returns a password hash
     *
     * @param string $password
     * @return string
     * @throws MissingPasswordHashServiceException
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     */
    protected function getPasswordHash(string $password): string
    {
        if (class_exists(PasswordHashFactory::class)) {
            $hashInstance = GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance('FE');
            $password = $hashInstance->getHashedPassword($password);
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
    public function isUserLoggedIn(): bool
    {
        return GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user')->isLoggedIn();
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
