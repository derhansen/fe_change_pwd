<?php
namespace Derhansen\FeChangePwd\Tests\Unit\Service;

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Derhansen\FeChangePwd\Service\SettingsService;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Class SettingsServiceTest
 */
class SettingsServiceTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function getPasswordExpiryTimestampReturnsExpectedResultDataProvider()
    {
        return [
            'no settings' => [
                [],
                new \DateTime(),
                0
            ],
            'passwordExpiration disabled' => [
                [
                    'passwordExpiration' => [
                        'enabled' => 0
                    ]
                ],
                new \DateTime(),
                0
            ],
            'default validityInDays of 90 days if not set' => [
                [
                    'passwordExpiration' => [
                        'enabled' => 1
                    ]
                ],
                \DateTime::createFromFormat('d.m.Y H:i:s e', '01.01.2018 00:00:00 UTC'),
                1522540800
            ],
            'sets configured validityInDays' => [
                [
                    'passwordExpiration' => [
                        'enabled' => 1,
                        'validityInDays' => 30
                    ]
                ],
                \DateTime::createFromFormat('d.m.Y H:i:s e', '01.01.2018 00:00:00 UTC'),
                1517356800
            ]
        ];
    }

    /**
     * @test
     * @dataProvider getPasswordExpiryTimestampReturnsExpectedResultDataProvider
     */
    public function getPasswordExpiryTimestampReturnsExpectedResult($settings, $currentDate, $expected)
    {
        $service = new SettingsService();

        $mockTsfe = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->setMethods(['getConfigArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['TSFE'] = $mockTsfe;
        $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_fechangepwd.']['settings.'] = $settings;

        $this->assertEquals($expected, $service->getPasswordExpiryTimestamp($currentDate));
    }
}
