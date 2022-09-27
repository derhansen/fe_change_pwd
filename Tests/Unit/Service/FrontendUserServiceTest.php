<?php

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Derhansen\FeChangePwd\Service\FrontendUserService;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class FrontendUserServiceTest
 */
class FrontendUserServiceTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function mustChangePasswordReturnsExpectedResultDataProvider()
    {
        return [
            'no frontend user' => [
                [],
                false,
            ],
            'must change password' => [
                [
                    'must_change_password' => 1,
                    'password_expiry_date' => 0,
                ],
                true,
            ],
            'password expired' => [
                [
                    'must_change_password' => 0,
                    'password_expiry_date' => 1538194307,
                ],
                true,
            ],
            'password not expired and no password change required' => [
                [
                    'must_change_password' => 0,
                    'password_expiry_date' => 0,
                ],
                false,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider mustChangePasswordReturnsExpectedResultDataProvider
     */
    public function mustChangePasswordReturnsExpectedResult($feUserRecord, $expected)
    {
        $userSessionManager = static::getMockBuilder(UserSessionManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $feUser = new FrontendUserAuthentication();
        $feUser->initializeUserSessionManager($userSessionManager);

        $service = new FrontendUserService();
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->fe_user = $feUser;
        self::assertEquals($expected, $service->mustChangePassword($feUserRecord));
    }
}
