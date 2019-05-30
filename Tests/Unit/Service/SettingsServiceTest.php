<?php
namespace Derhansen\FeChangePwd\Tests\Unit\Service;

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Derhansen\FeChangePwd\Service\SettingsService;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class SettingsServiceTest
 */
class SettingsServiceTest extends UnitTestCase
{
    /**
     * @var SettingsService
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
        $this->subject = new SettingsService();
    }

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
        $mockTsfe = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->setMethods(['getConfigArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['TSFE'] = $mockTsfe;

        $mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)
            ->setMethods(['getConfiguration'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockConfigurationManager->expects($this->once())->method('getConfiguration')
            ->will($this->returnValue($settings));
        $this->inject($this->subject, 'configurationManager', $mockConfigurationManager);

        $this->assertEquals($expected, $this->subject->getPasswordExpiryTimestamp($currentDate));
    }
}
