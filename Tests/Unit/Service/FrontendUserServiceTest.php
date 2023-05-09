<?php

declare(strict_types=1);

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Derhansen\FeChangePwd\Service\FrontendUserService;
use Derhansen\FeChangePwd\Service\SettingsService;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FrontendUserServiceTest extends UnitTestCase
{
    public static function mustChangePasswordReturnsExpectedResultDataProvider(): array
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
    public function mustChangePasswordReturnsExpectedResult(array $feUserRecord, bool $expected): void
    {
        $userSessionManager = $this->getMockBuilder(UserSessionManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $feUser = new FrontendUserAuthentication();
        $feUser->initializeUserSessionManager($userSessionManager);

        $mockSettingsService = $this->createMock(SettingsService::class);
        $mockContext = $this->createMock(Context::class);

        $service = new FrontendUserService($mockSettingsService, $mockContext);
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->fe_user = $feUser;
        self::assertEquals($expected, $service->mustChangePassword($feUserRecord));
    }
}
