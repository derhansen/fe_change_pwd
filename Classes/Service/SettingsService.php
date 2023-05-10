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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\TypoScriptAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service with helper function for settings handling
 */
class SettingsService
{
    protected array $settings = [];

    /**
     * Returns the settings
     */
    public function getSettings(ServerRequestInterface $request): array
    {
        if (empty($this->settings)) {
            try {
                $fullTypoScript = $request->getAttribute('frontend.typoscript')->getSetupArray();
            } catch (\Exception $e) {
                // An exception is thrown, when TypoScript setup array is not available. This is usually the case,
                // when the current page request is cached. Therefore, the TSFE TypoScript parsing is forced here.

                // Set a TypoScriptAspect which forces template parsing
                GeneralUtility::makeInstance(Context::class)
                    ->setAspect('typoscript', GeneralUtility::makeInstance(TypoScriptAspect::class, true));

                // Call TSFE getFromCache, which re-processes TypoScript respecting $forcedTemplateParsing property
                // from TypoScriptAspect
                $tsfe = $request->getAttribute('frontend.controller');
                $requestWithFullTypoScript = $tsfe->getFromCache($request);

                $fullTypoScript = $requestWithFullTypoScript->getAttribute('frontend.typoscript')->getSetupArray();
            }
            $settings = $fullTypoScript['plugin.']['tx_fechangepwd.']['settings.'] ?? [];
            $this->settings = GeneralUtility::removeDotsFromTS($settings);
        }
        return $this->settings;
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
