<?php

declare(strict_types=1);

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Derhansen\FeChangePwd\Controller;

use Derhansen\FeChangePwd\Domain\Model\Dto\ChangePassword;
use Derhansen\FeChangePwd\Event\AfterPasswordUpdatedEvent;
use Derhansen\FeChangePwd\Exception\InvalidEmailAddressException;
use Derhansen\FeChangePwd\Exception\MissingFeatureToggleException;
use Derhansen\FeChangePwd\Service\FrontendUserService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Security\Exception\InvalidHashException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class PasswordController extends ActionController
{
    public function __construct(
        protected readonly FrontendUserService $frontendUserService,
        protected readonly Features $features,
    ) {}

    public function initializeAction(): void
    {
        if (!$this->features->isFeatureEnabled('security.usePasswordPolicyForFrontendUsers')) {
            throw new MissingFeatureToggleException(
                'Extension fe_change_pwd relies on TYPO3 password policies being enabled. Please activate security.usePasswordPolicyForFrontendUsers feature toggle.',
                1683482651
            );
        }
    }

    /**
     * Edit action
     */
    public function editAction(): ResponseInterface
    {
        $changePassword = new ChangePassword();
        $changePassword->setChangeHmac($this->frontendUserService->getChangeHmac());
        $this->view->assignMultiple([
            'changePasswordReason' => $this->frontendUserService->getMustChangePasswordReason(),
            'changePassword' => $changePassword,
        ]);

        return $this->htmlResponse();
    }

    /**
     * Ensure a valid changeHmac is provided
     */
    public function initializeUpdateAction(): void
    {
        $changePasswordArray = $this->request->getArgument('changePassword');
        $changeHmac = $changePasswordArray['changeHmac'] ? (string)$changePasswordArray['changeHmac'] : '';
        if (!$this->frontendUserService->validateChangeHmac($changeHmac)) {
            throw new InvalidHashException(
                'Possible CSRF detected. Ensure a valid "changeHmac" is provided.',
                1572672118
            );
        }
    }

    /**
     * Update action
     *
     * @Extbase\Validate(param="changePassword", validator="Derhansen\FeChangePwd\Validation\Validator\ChangePasswordValidator")
     */
    public function updateAction(ChangePassword $changePassword): ResponseInterface
    {
        $this->frontendUserService->updatePassword($changePassword->getPassword1(), $this->settings);

        $this->eventDispatcher->dispatch(new AfterPasswordUpdatedEvent($changePassword, $this));

        if (isset($this->settings['afterPasswordChangeAction']) &&
            $this->settings['afterPasswordChangeAction'] === 'redirect') {
            $this->addFlashMessage(
                LocalizationUtility::translate('passwordUpdated', 'FeChangePwd'),
                LocalizationUtility::translate('passwordUpdated.title', 'FeChangePwd')
            );
            return $this->redirect('edit');
        }

        return $this->htmlResponse();
    }

    /**
     * Sends an email with the verification code to the current frontend user
     */
    public function sendChangePasswordCodeAction(): ResponseInterface
    {
        try {
            $this->frontendUserService->sendChangePasswordCodeEmail($this->settings, $this->request);
            $this->addFlashMessage(
                LocalizationUtility::translate('changePasswordCodeSent', 'FeChangePwd'),
                LocalizationUtility::translate('changePasswordCodeSent.title', 'FeChangePwd')
            );
        } catch (InvalidEmailAddressException $exception) {
            $this->addFlashMessage(
                LocalizationUtility::translate('changePasswordCodeInvalidEmail', 'FeChangePwd'),
                LocalizationUtility::translate('changePasswordCodeInvalidEmail.title', 'FeChangePwd'),
                ContextualFeedbackSeverity::ERROR
            );
        }

        return $this->redirect('edit');
    }

    /**
     * Suppress default flash messages
     */
    protected function getErrorFlashMessage(): bool
    {
        return false;
    }
}
