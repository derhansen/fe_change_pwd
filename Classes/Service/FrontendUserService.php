<?php

declare(strict_types=1);

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Derhansen\FeChangePwd\Service;

use Derhansen\FeChangePwd\Exception\InvalidEmailAddressException;
use Derhansen\FeChangePwd\Exception\InvalidUserException;
use Derhansen\FeChangePwd\Exception\MissingPasswordHashServiceException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Service class with frontend user helper functions
 */
class FrontendUserService
{
    public const SESSION_KEY = 'mustChangePasswordReason';

    public function __construct(
        protected readonly SettingsService $settingsService,
        protected readonly Context $context,
        protected readonly HashService $hashService
    ) {}

    /**
     * Returns if the frontend user must change the password
     */
    public function mustChangePassword(ServerRequestInterface $request, array $feUserRecord): bool
    {
        $reason = '';
        $result = false;
        $mustChangePassword = (bool)($feUserRecord['must_change_password'] ?? 0);
        $passwordExpiryTimestamp = $feUserRecord['password_expiry_date'] ?? 0;
        if ($mustChangePassword) {
            $reason = 'forcedChange';
            $result = true;
        } elseif (((int)$passwordExpiryTimestamp > 0 && (int)$passwordExpiryTimestamp < time())) {
            $reason = 'passwordExpired';
            $result = true;
        }

        if ($result) {
            // Store reason for password change in user session
            $frontendUser = $this->getFrontendUser($request);
            $frontendUser->setKey('ses', self::SESSION_KEY, $reason);
            $frontendUser->storeSessionData();
        }
        return $result;
    }

    /**
     * Returns the reason for the password change stored in the session
     */
    public function getMustChangePasswordReason(ServerRequestInterface $request): string
    {
        return (string)$this->getFrontendUser($request)->getKey('ses', self::SESSION_KEY);
    }

    /**
     * Updates the password of the current user if a current user session exist
     */
    public function updatePassword(ServerRequestInterface $request, string $newPassword, array $settings): void
    {
        if (!$this->isUserLoggedIn()) {
            return;
        }

        $password = $this->getPasswordHash($newPassword);

        $userTable = $this->getFrontendUser($request)->user_table;
        $userUid = $this->getFrontendUser($request)->user['uid'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($userTable);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->update($userTable)
            ->set('password', $password)
            ->set('must_change_password', 0)
            ->set('change_password_code_hash', '')
            ->set('change_password_code_expiry_date', 0)
            ->set('password_expiry_date', $this->settingsService->getPasswordExpiryTimestamp($settings))
            ->set('tstamp', $this->context->getPropertyFromAspect('date', 'timestamp'))
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($userUid, Connection::PARAM_INT)
                )
            )
            ->executeStatement();

        // Unset reason for password change in user session
        $frontendUser = $this->getFrontendUser($request);
        $frontendUser->setKey('ses', self::SESSION_KEY, null);
        $frontendUser->storeSessionData();

        // Destroy all sessions of the user except the current one
        $sessionManager = GeneralUtility::makeInstance(SessionManager::class);
        $sessionBackend = $sessionManager->getSessionBackend('FE');
        $sessionManager->invalidateAllSessionsByUserId(
            $sessionBackend,
            (int)$frontendUser->user['uid'],
            $frontendUser
        );
    }

    /**
     * Returns the changeHmac for the current logged in user
     */
    public function getChangeHmac(ServerRequestInterface $request): string
    {
        if (!$this->isUserLoggedIn()) {
            return '';
        }

        $frontendUser = $this->getFrontendUser($request);
        $userUid = $frontendUser->user['uid'];
        if (!is_int($userUid) || (int)$userUid <= 0) {
            throw new InvalidUserException('The fe_user uid is not a positive number.', 1574102778917);
        }

        $tstamp = $frontendUser->user['tstamp'];
        return $this->hashService->hmac('fe_user_' . $userUid . '_' . $tstamp, 'fe_change_pwd');
    }

    /**
     * Validates the given changeHmac
     */
    public function validateChangeHmac(ServerRequestInterface $request, string $changeHmac): bool
    {
        return $changeHmac !== '' && hash_equals($this->getChangeHmac($request), $changeHmac);
    }

    /**
     * Generates the change password code, saves it to the current frontend user record and sends an email
     * containing the change password code to the user
     */
    public function sendChangePasswordCodeEmail(array $settings, ServerRequestInterface $request): void
    {
        $frontendUser = $this->getFrontendUser($request);
        $recipientEmail = $frontendUser->user['email'] ?? '';
        if (!GeneralUtility::validEmail($recipientEmail)) {
            throw new InvalidEmailAddressException('Email address of frontend user is not valid');
        }

        $changePasswordCode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $validUntil = (new \DateTime())
            ->modify('+' . ($settings['requireChangePasswordCode']['validityInMinutes'] ?? 5) . ' minutes');

        $userTable = $frontendUser->user_table;
        $userUid = $frontendUser->user['uid'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($userTable);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->update($userTable)
            ->set('change_password_code_hash', $this->hashService->hmac($changePasswordCode, self::class))
            ->set('change_password_code_expiry_date', $validUntil->getTimestamp())
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($userUid, Connection::PARAM_INT)
                )
            )
            ->executeStatement();

        $userData = $frontendUser->user;
        unset($userData['password']);

        $email = GeneralUtility::makeInstance(FluidEmail::class);
        $email->setRequest($request);
        $email->setTemplate('ChangePasswordCode');

        $senderEmail = $settings['requireChangePasswordCode']['senderEmail'] ?? false;
        $sendername = $settings['requireChangePasswordCode']['senderName'] ?? '';
        if ($senderEmail && GeneralUtility::validEmail($senderEmail)) {
            $email->from(new Address($senderEmail, $sendername));
        }

        $email->to($recipientEmail);
        $email->format(FluidEmail::FORMAT_HTML);
        $email->assignMultiple([
            'userData' => $userData,
            'changePasswordCode' => $changePasswordCode,
            'validUntil' => $validUntil,
        ]);
        GeneralUtility::makeInstance(MailerInterface::class)->send($email);
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

    public function isUserLoggedIn(): bool
    {
        return $this->context->getAspect('frontend.user')->isLoggedIn();
    }

    protected function getFrontendUser(ServerRequestInterface $request): FrontendUserAuthentication
    {
        return $request->getAttribute('frontend.user');
    }
}
