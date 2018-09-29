<?php
namespace Derhansen\FeChangePwd\Tests\Unit\Service;

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Derhansen\FeChangePwd\Service\FrontendUserService;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Class FrontendUserServiceTest
 */
class FrontendUserServiceTest extends UnitTestCase
{
    /**
     * @var FrontendUserService
     */
    protected $subject;

    /**
     * Setup
     *
     * @return void
     */
    public function setup()
    {
        parent::setUp();
        $this->subject = new FrontendUserService();
    }

    /**
     * @return array
     */
    public function mustChangePasswordReturnsExpectedResultDataProvider()
    {
        return [
            'no frontend user' => [
                [],
                false
            ],
            'must change password' => [
                [
                    'must_change_password' => 1,
                    'password_expiry_date' => 0
                ],
                true
            ],
            'password expired' => [
                [
                    'must_change_password' => 0,
                    'password_expiry_date' => 1538194307
                ],
                true
            ],
            'password not expired and no password change required' => [
                [
                    'must_change_password' => 0,
                    'password_expiry_date' => 0
                ],
                false
            ],
        ];
    }

    /**
     * @test
     * @dataProvider mustChangePasswordReturnsExpectedResultDataProvider
     */
    public function mustChangePasswordReturnsExpectedResult($feUserRecord, $expected)
    {
        $this->assertEquals($expected, $this->subject->mustChangePassword($feUserRecord));
    }
}
