<?php

declare(strict_types=1);

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Derhansen\FeChangePwd\Validation\Validator;

use Derhansen\FeChangePwd\Domain\Model\Dto\ChangePassword;
use Derhansen\FeChangePwd\Service\FrontendUserService;
use Derhansen\FeChangePwd\Service\LocalizationService;
use Derhansen\FeChangePwd\Service\OldPasswordService;
use Derhansen\FeChangePwd\Service\SettingsService;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\PasswordPolicy\Event\EnrichPasswordValidationContextDataEvent;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyAction;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyValidator;
use TYPO3\CMS\Core\PasswordPolicy\Validator\Dto\ContextData;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Validator to validate data from the ChangePassword DTO
 */
class ChangePasswordValidator extends AbstractValidator
{
    protected SettingsService $settingsService;
    protected LocalizationService $localizationService;
    protected OldPasswordService $oldPasswordService;
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(array $options = [])
    {
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);
        $this->localizationService = GeneralUtility::makeInstance(LocalizationService::class);
        $this->oldPasswordService = GeneralUtility::makeInstance(OldPasswordService::class);
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
    }

    /**
     * Validates the password of the given ChangePassword object against the configured password complexity
     *
     * @param ChangePassword $value
     */
    protected function isValid($value): void
    {
        $settings = $this->settingsService->getSettings($this->getRequest());

        // Early return if old password is required, but either empty or not valid
        if (isset($settings['requireCurrentPassword']['enabled'])
            && (bool)$settings['requireCurrentPassword']['enabled']
            && !$value->getSkipCurrentPasswordCheck()
        ) {
            $requireCurrentPasswordResult = $this->evaluateRequireCurrentPassword($value);
            if ($requireCurrentPasswordResult === false) {
                return;
            }
        }

        // Early return if change password code is required, but either empty or not valid
        if (isset($settings['requireChangePasswordCode']['enabled'])
            && (bool)$settings['requireChangePasswordCode']['enabled']
            && $this->evaluateChangePasswordCode($value) === false
        ) {
            return;
        }

        // Early return if no passwords are given
        if ($value->getPassword1() === '' || $value->getPassword2() === '') {
            $this->addError(
                $this->localizationService->translate('passwordFieldsEmptyOrNotBothFilledOut'),
                1537701950
            );

            return;
        }

        if ($value->getPassword1() !== $value->getPassword2()) {
            $this->addError(
                $this->localizationService->translate('passwordsDoNotMatch'),
                1537701950
            );
            // Early return, no other checks need to be done if passwords do not match
            return;
        }

        $userData = $this->getFrontendUser()->user ?? [];

        // Validate against password policy
        $passwordPolicyValidator = $this->getPasswordPolicyValidator();
        $contextData = new ContextData(
            loginMode: 'FE',
            currentPasswordHash: $userData['password']
        );
        $contextData->setData('currentUsername', $userData['username']);
        $contextData->setData('currentFirstname', $userData['first_name']);
        $contextData->setData('currentLastname', $userData['last_name']);
        $event = $this->eventDispatcher->dispatch(
            new EnrichPasswordValidationContextDataEvent(
                $contextData,
                $userData,
                self::class
            )
        );
        $contextData = $event->getContextData();

        if (!$passwordPolicyValidator->isValidPassword($value->getPassword1(), $contextData)) {
            foreach ($passwordPolicyValidator->getValidationErrors() as $validationError) {
                $this->addError(
                    $validationError,
                    1683436079
                );
            }
        }
    }

    /**
     * Evaluates if the current password is not empty and valid
     */
    protected function evaluateRequireCurrentPassword(ChangePassword $changePassword): bool
    {
        $result = true;
        $oldPasswordEmpty = $changePassword->getCurrentPassword() === '';
        if ($oldPasswordEmpty) {
            $result = false;
            $this->addError(
                $this->localizationService->translate('currentPasswordEmpty'),
                1570880411
            );
        }

        if ($oldPasswordEmpty === false
            && !$this->oldPasswordService->checkEqualsOldPassword(
                $changePassword->getCurrentPassword(),
                $this->getFrontendUser()->user['password']
            )
        ) {
            $result = false;
            $this->addError(
                $this->localizationService->translate('currentPasswordFailure'),
                1570880417
            );
        }
        return $result;
    }

    /**
     * Evaluates the change password code
     */
    protected function evaluateChangePasswordCode(ChangePassword $changePassword): bool
    {
        $currentHash = $this->getFrontendUser()->user['change_password_code_hash'] ?? '';
        $calculatedHash = GeneralUtility::hmac($changePassword->getChangePasswordCode(), FrontendUserService::class);
        $expirationTime = (int)($this->getFrontendUser()->user['change_password_code_expiry_date'] ?? 0);

        if (empty($changePassword->getChangePasswordCode())) {
            $this->addError(
                $this->localizationService->translate('changePasswordCode.empty'),
                1701451678
            );
            return false;
        }

        if ($currentHash === ''
            || $expirationTime === 0
            || $expirationTime < time()
            || !hash_equals($currentHash, $calculatedHash)
        ) {
            $this->addError(
                $this->localizationService->translate('changePasswordCode.invalidOrExpired'),
                1701451180
            );
            return false;
        }

        return true;
    }

    protected function getPasswordPolicyValidator(): PasswordPolicyValidator
    {
        $passwordPolicy = $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordPolicy'] ?? 'default';
        return GeneralUtility::makeInstance(
            PasswordPolicyValidator::class,
            PasswordPolicyAction::UPDATE_USER_PASSWORD,
            is_string($passwordPolicy) ? $passwordPolicy : ''
        );
    }

    protected function getFrontendUser(): AbstractUserAuthentication
    {
        return $this->getRequest()->getAttribute('frontend.user');
    }

    protected function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }
}
