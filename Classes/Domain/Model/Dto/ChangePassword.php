<?php
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
     * @validate NotEmpty
     */
    protected $password1 = '';

    /**
     * @var string
     * @validate NotEmpty
     */
    protected $password2 = '';

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
}
