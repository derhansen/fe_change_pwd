<?php

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Derhansen\FeChangePwd\Tests\Unit\Validation\Validator;

use Derhansen\FeChangePwd\Domain\Model\Dto\ChangePassword;
use Derhansen\FeChangePwd\Service\LocalizationService;
use Derhansen\FeChangePwd\Service\SettingsService;
use Derhansen\FeChangePwd\Validation\Validator\ChangePasswordValidator;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ChangePasswordValidatorTest
 */
class ChangePasswordValidatorTest extends UnitTestCase
{
    protected ChangePasswordValidator $validator;

    /**
     * Initialize validator
     */
    public function initialize(): void
    {
        $this->validator = $this->getAccessibleMock(
            ChangePasswordValidator::class,
            ['translateErrorMessage'],
            [],
            '',
            false
        );

        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest();
    }

    /**
     * @test
     */
    public function noCurrentPasswordGivenTest(): void
    {
        $this->initialize();

        $changePassword = new ChangePassword();
        $changePassword->setCurrentPassword('');

        $settings = [
            'requireCurrentPassword' => [
                'enabled' => 1,
            ],
        ];

        $mockSettingsService = $this->getMockBuilder(SettingsService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSettingsService->expects(self::once())->method('getSettings')->willReturn($settings);
        $this->validator->_set('settingsService', $mockSettingsService);

        $mockLocalizationService = $this->getMockBuilder(LocalizationService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockLocalizationService->expects(self::any())->method('translate')->willReturn('');
        $this->validator->_set('localizationService', $mockLocalizationService);

        self::assertEquals(1570880411, $this->validator->validate($changePassword)->getErrors()[0]->getCode());
    }

    /**
     * @test
     */
    public function currentPasswordValidationSkipped(): void
    {
        $this->initialize();

        $changePassword = new ChangePassword();
        $changePassword->setCurrentPassword('123456');
        $changePassword->setSkipCurrentPasswordCheck(true);

        $settings = [
            'requireCurrentPassword' => [
                'enabled' => 1,
            ],
        ];

        $mockSettingsService = $this->getMockBuilder(SettingsService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSettingsService->expects(self::once())->method('getSettings')->willReturn($settings);
        $this->validator->_set('settingsService', $mockSettingsService);

        $mockLocalizationService = $this->getMockBuilder(LocalizationService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockLocalizationService->expects(self::any())->method('translate')->willReturn('');
        $this->validator->_set('localizationService', $mockLocalizationService);

        self::assertEquals(1537701950, $this->validator->validate($changePassword)->getErrors()[0]->getCode());
    }
}
