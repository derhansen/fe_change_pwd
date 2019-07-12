<?php
declare(strict_types=1);
namespace Derhansen\FeChangePwd\Validation\Validator;

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Derhansen\FeChangePwd\Domain\Model\Dto\ChangePassword;
use Derhansen\FeChangePwd\Service\LocalizationService;
use Derhansen\FeChangePwd\Service\OldPasswordService;
use Derhansen\FeChangePwd\Service\PwnedPasswordsService;
use Derhansen\FeChangePwd\Service\SettingsService;

/**
 * Class RegistrationValidator
 */
class ChangePasswordValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator
{
    /**
     * Available password checks
     *
     * @var array
     */
    protected $checks = [
        'capitalCharCheck',
        'lowerCaseCharCheck',
        'digitCheck',
        'specialCharCheck',
    ];

    /**
     * @var SettingsService
     */
    protected $settingsService = null;

    /**
     * @var LocalizationService
     */
    protected $localizationService = null;

    /**
     * @var PwnedPasswordsService
     */
    protected $pwnedPasswordsService = null;

    /**
     * @var OldPasswordService
     */
    protected $oldPasswordService = null;

    /**
     * @param SettingsService $settingsService
     */
    public function injectSettingsService(\Derhansen\FeChangePwd\Service\SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * @param LocalizationService $localizationService
     */
    public function injectLocalizationService(
        \Derhansen\FeChangePwd\Service\LocalizationService $localizationService
    ) {
        $this->localizationService = $localizationService;
    }

    /**
     * @param OldPasswordService $oldPasswordService
     */
    public function injectOldPasswordService(\Derhansen\FeChangePwd\Service\OldPasswordService $oldPasswordService)
    {
        $this->oldPasswordService = $oldPasswordService;
    }

    /**
     * @param PwnedPasswordsService $PwnedPasswordsService
     */
    public function injectPwnedPasswordsService(
        \Derhansen\FeChangePwd\Service\PwnedPasswordsService $PwnedPasswordsService
    ) {
        $this->pwnedPasswordsService = $PwnedPasswordsService;
    }

    /**
     * Validates the password of the given ChangePassword object against the configured password complexity
     *
     * @param ChangePassword $value
     *
     * @return bool
     */
    protected function isValid($value)
    {
        $result = true;
        $settings = $this->settingsService->getSettings();

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
     * @return void
     */
    protected function evaluateMinLengthCheck(ChangePassword $changePassword, int $minLength)
    {
        if (strlen($changePassword->getPassword1()) < $minLength) {
            $this->addError(
                $this->localizationService->translate('passwordComplexity.failure.minLength', [$minLength]),
                1537898028
            );
        };
    }

    /**
     * Evaluates the password complexity in regards to the given check
     *
     * @param ChangePassword $changePassword
     * @param string $check
     * @return void
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
     * @return void
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
     * @return void
     */
    protected function evaluateOldPasswordCheck(ChangePassword $changePassword)
    {
        if ($this->oldPasswordService->checkNewEqualsOldPassword($changePassword->getPassword1())) {
            $this->addError(
                $this->localizationService->translate('oldPasswordFailure'),
                1537898030
            );
        }
    }
}
