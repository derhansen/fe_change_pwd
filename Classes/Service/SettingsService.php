<?php

declare(strict_types=1);

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Derhansen\FeChangePwd\Service;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\TypoScriptAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SettingsService
 */
class SettingsService
{
    /**
     * @var mixed
     */
    protected $settings;

    /**
     * Returns the settings
     *
     * @return array
     */
    public function getSettings(): array
    {
        if ($this->settings === null) {
            // Ensure, TSFE setup is loaded for cached pages
            if ($GLOBALS['TSFE']->tmpl === null || $GLOBALS['TSFE']->tmpl && empty($GLOBALS['TSFE']->tmpl->setup)) {
                GeneralUtility::makeInstance(Context::class)
                    ->setAspect('typoscript', GeneralUtility::makeInstance(TypoScriptAspect::class, true));
                $GLOBALS['TSFE']->getConfigArray();
            }

            $settings = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_fechangepwd.']['settings.'] ?? [];
            $this->settings = GeneralUtility::removeDotsFromTS($settings);
        }
        return $this->settings;
    }

    /**
     * Returns the password expiry timestamp depending on the configured setting switch. If password expiry is not
     * enabled, 0 is returned. If no password validity in days is configured, 90 days is taken as fallback
     *
     * @param \DateTime|null $currentDate
     * @return int
     */
    public function getPasswordExpiryTimestamp(?\DateTime $currentDate = null): int
    {
        if (!$currentDate) {
            $currentDate = new \DateTime();
        }
        $result = 0;
        $settings = $this->getSettings();
        if (isset($settings['passwordExpiration']['enabled']) && (bool)$settings['passwordExpiration']['enabled']) {
            $validityInDays = $settings['passwordExpiration']['validityInDays'] ?? 90;
            $currentDate->modify('+' . $validityInDays . 'days');
            $result = $currentDate->getTimestamp();
        }
        return $result;
    }
}
