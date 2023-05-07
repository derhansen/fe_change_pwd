<?php

declare(strict_types=1);

namespace Derhansen\FeChangePwd\Tests\Unit\Service;

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Derhansen\FeChangePwd\Service\SettingsService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SettingsServiceTest extends UnitTestCase
{
    public static function getPasswordExpiryTimestampReturnsExpectedResultDataProvider(): array
    {
        return [
            'no settings' => [
                [],
                new \DateTime(),
                0,
            ],
            'passwordExpiration disabled' => [
                [
                    'passwordExpiration' => [
                        'enabled' => 0,
                    ],
                ],
                new \DateTime(),
                0,
            ],
            'default validityInDays of 90 days if not set' => [
                [
                    'passwordExpiration' => [
                        'enabled' => 1,
                    ],
                ],
                \DateTime::createFromFormat('d.m.Y H:i:s e', '01.01.2018 00:00:00 UTC'),
                1522540800,
            ],
            'sets configured validityInDays' => [
                [
                    'passwordExpiration' => [
                        'enabled' => 1,
                        'validityInDays' => 30,
                    ],
                ],
                \DateTime::createFromFormat('d.m.Y H:i:s e', '01.01.2018 00:00:00 UTC'),
                1517356800,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getPasswordExpiryTimestampReturnsExpectedResultDataProvider
     */
    public function getPasswordExpiryTimestampReturnsExpectedResult(
        array $settings,
        \DateTime $currentDate,
        int $expected
    ): void {
        $service = new SettingsService();
        self::assertEquals($expected, $service->getPasswordExpiryTimestamp($settings, $currentDate));
    }

    // @todo getSettings Test
}
