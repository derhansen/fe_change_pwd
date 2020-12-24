<?php
declare(strict_types=1);
namespace Derhansen\FeChangePwd\Controller;

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Derhansen\FeChangePwd\Domain\Model\Dto\ChangePassword;
use Derhansen\FeChangePwd\Service\FrontendUserService;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class PasswordController
 */
class PasswordController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * @var FrontendUserService
     */
    protected $frontendUserService = null;

    /**
     * @param FrontendUserService $frontendUserService
     */
    public function injectFrontendUserService(
        \Derhansen\FeChangePwd\Service\FrontendUserService $frontendUserService
    ) {
        $this->frontendUserService = $frontendUserService;
    }

    /**
     * Edit action
     *
     * @return void
     */
    public function editAction()
    {
        $changePassword = $this->objectManager->get(ChangePassword::class);
        $changePassword->setChangeHmac($this->frontendUserService->getChangeHmac());
        $this->view->assignMultiple([
            'changePasswordReason' => $this->frontendUserService->getMustChangePasswordReason(),
            'changePassword' => $changePassword
        ]);
    }

    /**
     * Ensure a valid changeHmac is provided
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     * @throws \TYPO3\CMS\Extbase\Security\Exception\InvalidHashException
     */
    public function initializeUpdateAction()
    {
        $changePasswordArray = $this->request->getArgument('changePassword');
        $changeHmac = $changePasswordArray['changeHmac'] ? (string)$changePasswordArray['changeHmac'] : '';
        if (!$this->frontendUserService->validateChangeHmac($changeHmac)) {
            throw new \TYPO3\CMS\Extbase\Security\Exception\InvalidHashException(
                'Possible CSRF detected. Ensure a valid "changeHmac" is provided.',
                1572672118931
            );
        }
    }

    /**
     * Update action
     *
     * @param \Derhansen\FeChangePwd\Domain\Model\Dto\ChangePassword $changePassword
     * @validate $changePassword \Derhansen\FeChangePwd\Validation\Validator\ChangePasswordValidator
     *
     * @return void
     */
    public function updateAction(ChangePassword $changePassword)
    {
        $this->frontendUserService->updatePassword($changePassword->getPassword1());
        if (isset($this->settings['afterPasswordChangeAction']) &&
            $this->settings['afterPasswordChangeAction'] === 'redirect') {
            $this->addFlashMessage(
                LocalizationUtility::translate('passwordUpdated', 'FeChangePwd'),
                LocalizationUtility::translate('passwordUpdated.title', 'FeChangePwd')
            );
            $this->redirect('edit');
        }
    }

    /**
     * Suppress default flash messages
     *
     * @return bool
     */
    protected function getErrorFlashMessage()
    {
        return false;
    }
}
