<?php
namespace Derhansen\FeChangePwd\Controller;

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Derhansen\FeChangePwd\Domain\Model\Dto\ChangePassword;
use Derhansen\FeChangePwd\Service\FrontendUserService;

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
        $this->view->assignMultiple([
            'changePassword' => $changePassword
        ]);
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
    }
}
