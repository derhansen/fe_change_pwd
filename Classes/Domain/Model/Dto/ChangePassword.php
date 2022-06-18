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
    protected string $feUserPasswordHash = '';
    protected string $changeHmac = '';
    protected bool $skipCurrentPasswordCheck = false;

    public function getPassword1(): string
    {
        return $this->password1;
    }

    public function setPassword1(string $password1): void
    {
        $this->password1 = $password1;
    }

    public function getPassword2(): string
    {
        return $this->password2;
    }

    public function setPassword2(string $password2): void
    {
        $this->password2 = $password2;
    }

    public function getCurrentPassword(): string
    {
        return $this->currentPassword;
    }

    public function setCurrentPassword(string $currentPassword): void
    {
        $this->currentPassword = $currentPassword;
    }

    public function getChangeHmac(): string
    {
        return $this->changeHmac;
    }

    public function setChangeHmac(string $changeHmac): void
    {
        $this->changeHmac = $changeHmac;
    }

    public function getSkipCurrentPasswordCheck(): bool
    {
        return $this->skipCurrentPasswordCheck;
    }

    public function setSkipCurrentPasswordCheck(bool $skipCurrentPasswordCheck): void
    {
        $this->skipCurrentPasswordCheck = $skipCurrentPasswordCheck;
    }

    public function getFeUserPasswordHash(): string
    {
        return $this->feUserPasswordHash;
    }

    public function setFeUserPasswordHash(string $feUserPasswordHash): void
    {
        $this->feUserPasswordHash = $feUserPasswordHash;
    }
}
