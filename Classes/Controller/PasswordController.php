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
use Derhansen\FeChangePwd\Service\FrontendUserService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Security\Exception\InvalidHashException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class PasswordController
 */
class PasswordController extends ActionController
{
    protected FrontendUserService $frontendUserService;

    /**
     * @param FrontendUserService $frontendUserService
     */
    public function injectFrontendUserService(
        FrontendUserService $frontendUserService
    ) {
        $this->frontendUserService = $frontendUserService;
    }

    /**
     * Edit action
     *
     * @return ResponseInterface
     */
    public function editAction(): ResponseInterface
    {
        $changePassword = new ChangePassword();
        $changePassword->setChangeHmac($this->frontendUserService->getChangeHmac());
        $this->view->assignMultiple([
            'changePasswordReason' => $this->frontendUserService->getMustChangePasswordReason(),
            'changePassword' => $changePassword
        ]);

        return $this->htmlResponse();
    }

    /**
     * Ensure a valid changeHmac is provided
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     * @throws InvalidHashException
     */
    public function initializeUpdateAction()
    {
        $changePasswordArray = $this->request->getArgument('changePassword');
        $changeHmac = $changePasswordArray['changeHmac'] ? (string)$changePasswordArray['changeHmac'] : '';
        if (!$this->frontendUserService->validateChangeHmac($changeHmac)) {
            throw new InvalidHashException(
                'Possible CSRF detected. Ensure a valid "changeHmac" is provided.',
                1572672118931
            );
        }
    }

    /**
     * Update action
     *
     * @param \Derhansen\FeChangePwd\Domain\Model\Dto\ChangePassword $changePassword
     * @return ResponseInterface
     * @Extbase\Validate(param="changePassword", validator="Derhansen\FeChangePwd\Validation\Validator\ChangePasswordValidator")
     */
    public function updateAction(ChangePassword $changePassword): ResponseInterface
    {
        $this->frontendUserService->updatePassword($changePassword->getPassword1());

        $this->eventDispatcher->dispatch(new AfterPasswordUpdatedEvent($changePassword, $this));

        if (isset($this->settings['afterPasswordChangeAction']) &&
            $this->settings['afterPasswordChangeAction'] === 'redirect') {
            $this->addFlashMessage(
                LocalizationUtility::translate('passwordUpdated', 'FeChangePwd'),
                LocalizationUtility::translate('passwordUpdated.title', 'FeChangePwd')
            );
            $this->redirect('edit');
        }

        return $this->htmlResponse();
    }

    /**
     * Suppress default flash messages
     *
     * @return bool
     */
    protected function getErrorFlashMessage(): bool
    {
        return false;
    }
}
