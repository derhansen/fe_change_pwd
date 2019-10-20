<?php
declare(strict_types=1);
namespace Derhansen\FeChangePwd\Domain\Model\Dto;

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

/**
 * Class ChangePassword
 */
class ChangePassword
{
    /**
     * @var string
     */
    protected $password1 = '';

    /**
     * @var string
     */
    protected $password2 = '';

    /**
     * @var string
     */
    protected $currentPassword = '';

    /**
     * @return string
     */
    public function getPassword1()
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
    public function getPassword2()
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
    public function getCurrentPassword()
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
}
