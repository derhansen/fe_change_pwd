<?php
namespace Derhansen\FeChangePwd\Service;

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PageAccessService
 */
class PageAccessService
{
    /**
     * @var SettingsService
     */
    protected $settingsService = null;

    /**
     * @param SettingsService $settingsService
     */
    public function injectSettingsService(\Derhansen\FeChangePwd\Service\SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Returns the redirect mode
     *
     * @return string
     */
    public function getRedirectMode()
    {
        $settings = $this->settingsService->getSettings();
        if ((bool)$settings['redirect']['allAccessProtectedPages']) {
            $redirectMode = 'allAccessProtectedPages';
        } elseif (isset($settings['redirect']['includePageUids']) && $settings['redirect']['includePageUids'] !== '') {
            $redirectMode = 'includePageUids';
        } else {
            $redirectMode = '';
        }
        return $redirectMode;
    }

    /**
     * Returns the configured redirect PID
     *
     * @return mixed
     */
    public function getRedirectPid()
    {
        $settings = $this->settingsService->getSettings();
        if (!isset($settings['changePasswordPid']) || (int)$settings['changePasswordPid'] === 0) {
            // @todo throw exception
        }
        return (int)$settings['changePasswordPid'];
    }

    /**
     * Returns, if the given page uid is configured as included for redirects
     *
     * @param int $pageUid
     * @return bool
     */
    public function isIncludePage($pageUid)
    {
        $settings = $this->settingsService->getSettings();
        if (isset($settings['redirect']['includePageUids']) && $settings['redirect']['includePageUids'] !== '') {
            $excludePids = GeneralUtility::intExplode(',', $settings['redirect']['includePageUids'], true);
        } else {
            $excludePids = [];
        }
        return in_array($pageUid, $excludePids, true);
    }

    /**
     * Returns, if the given page uid is configured as excluded from redirects
     *
     * @param int $pageUid
     * @return bool
     */
    public function isExcludePage($pageUid)
    {
        $settings = $this->settingsService->getSettings();
        if (isset($settings['redirect']['excludePageUids']) && $settings['redirect']['excludePageUids'] !== '') {
            $excludePids = GeneralUtility::intExplode(',', $settings['redirect']['excludePageUids'], true);
        } else {
            $excludePids = [];
        }
        // Always add the changePasswordPid as exclude PID
        $excludePids[] = (int)$settings['changePasswordPid'];
        return in_array($pageUid, $excludePids, true);
    }

    /**
     * Returns, if the there is an access protected page in the rootline respecting 'extendToSubpages' setting
     *
     * @param array $rootline
     * @return bool
     */
    public function isAccessProtectedPageInRootline($rootline)
    {
        $isAccessProtected = false;
        $loop = 0;
        foreach ($rootline as $rootlinePage) {
            $isPublic = ($rootlinePage['fe_group'] === '' || $rootlinePage['fe_group'] === '0');
            $extendToSubpages = (bool)$rootlinePage['extendToSubpages'];
            if (!$isPublic || (!$isPublic && $extendToSubpages && $loop >= 1)) {
                $isAccessProtected = true;
                break;
            }
            $loop++;
        }
        return $isAccessProtected;
    }
}
