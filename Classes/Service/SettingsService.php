<?php

declare(strict_types=1);

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Derhansen\FeChangePwd\Service;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service with helper function for handling typoscript settings
 */
class SettingsService
{
    protected array $settings = [];

    /**
     * Returns TypoScript settings
     */
    public function getTypoScriptSettings(ServerRequestInterface $request): array
    {
        if (empty($this->settings)) {
            $fullTypoScript = $request->getAttribute('frontend.typoscript')->getSetupArray();
            $settings = $fullTypoScript['plugin.']['tx_fechangepwd.']['settings.'] ?? [];
            $this->settings = GeneralUtility::removeDotsFromTS($settings);
        }
        return $this->settings;
    }

    /**
     * Returns site settings for the extension
     */
    public function getSiteSettings(ServerRequestInterface $request): array
    {
        /** @var Site $site */
        $site = $request->getAttribute('site');
        return $site->getSettings()->get('fe_change_pwd');
    }

    /**
     * Returns the password expiry timestamp depending on the configured setting switch. If password expiry is not
     * enabled, 0 is returned. If no password validity in days is configured, 90 days is taken as fallback
     */
    public function getPasswordExpiryTimestamp(array $settings, ?\DateTime $currentDate = null): int
    {
        if (!$currentDate) {
            $currentDate = new \DateTime();
        }
        $result = 0;
        if (isset($settings['passwordExpiration']['enabled']) && (bool)$settings['passwordExpiration']['enabled']) {
            $validityInDays = $settings['passwordExpiration']['validityInDays'] ?? 90;
            $currentDate->modify('+' . $validityInDays . 'days');
            $result = $currentDate->getTimestamp();
        }
        return $result;
    }
}
