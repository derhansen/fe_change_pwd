<?php

declare(strict_types=1);

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Derhansen\FeChangePwd\Domain\Model\Dto;

/**
 * Class ChangePassword
 */
class ChangePassword
{
    protected string $password1 = '';
    protected string $password2 = '';
    protected string $currentPassword = '';
    protected string $changeHmac = '';

    /**
     * @return string
     */
    public function getPassword1(): string
    {
        return $this->password1;
    }

    /**
     * @param string $password1
     */
    public function setPassword1(string $password1)
    {
        $this->password1 = $password1;
    }

    /**
     * @return string
     */
    public function getPassword2(): string
    {
        return $this->password2;
    }

    /**
     * @param string $password2
     */
    public function setPassword2(string $password2)
    {
        $this->password2 = $password2;
    }

    /**
     * @return string
     */
    public function getCurrentPassword(): string
    {
        return $this->currentPassword;
    }

    /**
     * @param string $currentPassword
     */
    public function setCurrentPassword(string $currentPassword)
    {
        $this->currentPassword = $currentPassword;
    }

    /**
     * @return string
     */
    public function getChangeHmac(): string
    {
        return $this->changeHmac;
    }

    /**
     * @param string $changeHmac
     */
    public function setChangeHmac(string $changeHmac)
    {
        $this->changeHmac = $changeHmac;
    }
}
