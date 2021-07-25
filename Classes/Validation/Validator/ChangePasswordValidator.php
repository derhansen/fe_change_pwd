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
use Derhansen\FeChangePwd\Service\LocalizationService;
use Derhansen\FeChangePwd\Service\OldPasswordService;
use Derhansen\FeChangePwd\Service\PwnedPasswordsService;
use Derhansen\FeChangePwd\Service\SettingsService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Class ChangePasswordValidator
 */
class ChangePasswordValidator extends AbstractValidator
{
    protected array $checks = [
        'capitalCharCheck',
        'lowerCaseCharCheck',
        'digitCheck',
        'specialCharCheck',
    ];

    protected SettingsService $settingsService;
    protected LocalizationService $localizationService;
    protected OldPasswordService $oldPasswordService;
    protected PwnedPasswordsService $pwnedPasswordsService;

    public function __construct(array $options = [])
    {
        $this->settingsService = GeneralUtility::makeInstance(SettingsService::class);
        $this->localizationService = GeneralUtility::makeInstance(LocalizationService::class);
        $this->oldPasswordService = GeneralUtility::makeInstance(OldPasswordService::class);
        $this->pwnedPasswordsService = GeneralUtility::makeInstance(PwnedPasswordsService::class);
        parent::__construct($options);
    }

    /**
     * Validates the password of the given ChangePassword object against the configured password complexity
     *
     * @param ChangePassword $value
     *
     * @return bool
     */
    protected function isValid($value): bool
    {
        $result = true;
        $settings = $this->settingsService->getSettings();

        // Early return if old password is required, but either empty or not valid
        if (isset($settings['requireCurrentPassword']['enabled']) &&
            (bool)$settings['requireCurrentPassword']['enabled'] &&
            !$value->getSkipCurrentPasswordCheck()
        ) {
            $requireCurrentPasswordResult = $this->evaluateRequireCurrentPassword($value);
            if ($requireCurrentPasswordResult === false) {
                return false;
            }
        }

        // Early return if no passwords are given
        if ($value->getPassword1() === '' || $value->getPassword2() === '') {
            $this->addError(
                $this->localizationService->translate('passwordFieldsEmptyOrNotBothFilledOut'),
                1537701950
            );

            return false;
        }

        if ($value->getPassword1() !== $value->getPassword2()) {
            $this->addError(
                $this->localizationService->translate('passwordsDoNotMatch'),
                1537701950
            );
            // Early return, no other checks need to be done if passwords do not match
            return false;
        }

        if (isset($settings['passwordComplexity']['minLength'])) {
            $this->evaluateMinLengthCheck($value, (int)$settings['passwordComplexity']['minLength']);
        }

        foreach ($this->checks as $check) {
            if (isset($settings['passwordComplexity'][$check]) &&
                (bool)$settings['passwordComplexity'][$check]
            ) {
                $this->evaluatePasswordCheck($value, $check);
            }
        }

        if (isset($settings['pwnedpasswordsCheck']['enabled']) && (bool)$settings['pwnedpasswordsCheck']['enabled']) {
            $this->evaluatePwnedPasswordCheck($value);
        }

        if (isset($settings['oldPasswordCheck']['enabled']) && (bool)$settings['oldPasswordCheck']['enabled']) {
            $this->evaluateOldPasswordCheck($value);
        }

        if ($this->result->hasErrors()) {
            $result = false;
        }

        return $result;
    }

    /**
     * Checks if the password complexity in regards to minimum password length in met
     *
     * @param ChangePassword $changePassword
     * @param int $minLength
     */
    protected function evaluateMinLengthCheck(ChangePassword $changePassword, int $minLength)
    {
        if (strlen($changePassword->getPassword1()) < $minLength) {
            $this->addError(
                $this->localizationService->translate('passwordComplexity.failure.minLength', [$minLength]),
                1537898028
            );
        }
    }

    /**
     * Evaluates the password complexity in regards to the given check
     *
     * @param ChangePassword $changePassword
     * @param string $check
     */
    protected function evaluatePasswordCheck(ChangePassword $changePassword, $check)
    {
        $patterns = [
            'capitalCharCheck' => '/[A-Z]/',
            'lowerCaseCharCheck' => '/[a-z]/',
            'digitCheck' => '/[0-9]/',
            'specialCharCheck' => '/[^0-9a-z]/i'
        ];

        if (isset($patterns[$check])) {
            if (!preg_match($patterns[$check], $changePassword->getPassword1()) > 0) {
                $this->addError(
                    $this->localizationService->translate('passwordComplexity.failure.' . $check),
                    1537898029
                );
            }
        }
    }

    /**
     * Evaluates the password using the pwnedpasswords API
     *
     * @param ChangePassword $changePassword
     */
    protected function evaluatePwnedPasswordCheck(ChangePassword $changePassword)
    {
        $foundCount = $this->pwnedPasswordsService->checkPassword($changePassword->getPassword1());
        if ($foundCount > 0) {
            $this->addError(
                $this->localizationService->translate('pwnedPasswordFailure', [$foundCount]),
                1537898030
            );
        }
    }

    /**
     * Evaluates the password against the current password
     *
     * @param ChangePassword $changePassword
     */
    protected function evaluateOldPasswordCheck(ChangePassword $changePassword)
    {
        if ($this->oldPasswordService->checkEqualsOldPassword($changePassword->getPassword1())) {
            $this->addError(
                $this->localizationService->translate('oldPasswordFailure'),
                1570880406
            );
        }
    }

    /**
     * Evaluates if the current password is not empty and valid
     *
     * @param ChangePassword $changePassword
     * @return bool
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

        if ($oldPasswordEmpty === false &&
            !$this->oldPasswordService->checkEqualsOldPassword($changePassword->getCurrentPassword())
        ) {
            $result = false;
            $this->addError(
                $this->localizationService->translate('currentPasswordFailure'),
                1570880417
            );
        }
        return $result;
    }
}
