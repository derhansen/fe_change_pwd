<?php

declare(strict_types=1);

namespace Derhansen\FeChangePwd\Event;

use Derhansen\FeChangePwd\Controller\PasswordController;
use Derhansen\FeChangePwd\Domain\Model\Dto\ChangePassword;

/**
 * This event is triggered after the password has been updated
 */
final class AfterPasswordUpdatedEvent
{
    private ChangePassword $changePassword;
    private PasswordController $passwordController;

    public function __construct(ChangePassword $changePassword, PasswordController $passwordController)
    {
        $this->changePassword = $changePassword;
        $this->passwordController = $passwordController;
    }

    public function getChangePassword(): ChangePassword
    {
        return $this->changePassword;
    }

    public function getPasswordController(): PasswordController
    {
        return $this->passwordController;
    }
}
