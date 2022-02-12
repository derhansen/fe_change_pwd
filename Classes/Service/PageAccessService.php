<?php

declare(strict_types=1);

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Derhansen\FeChangePwd\Service;

use Derhansen\FeChangePwd\Exception\NoChangePasswordPidException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Class PageAccessService
 */
class PageAccessService
{
    protected SettingsService $settingsService;

    /**
     * @param SettingsService $settingsService
     */
    public function injectSettingsService(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Returns the redirect mode
     *
     * @return string
     */
    public function getRedirectMode(): string
    {
        $settings = $this->settingsService->getSettings();
        if (($settings['redirect']['allAccessProtectedPages'] ?? false)) {
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
            throw new NoChangePasswordPidException(
                'settings.changePasswordPid is not set or zero',
                1580040840163
            );
        }
        return (int)$settings['changePasswordPid'];
    }

    /**
     * Returns, if the given page uid is configured as included for redirects
     *
     * @param int $pageUid
     * @return bool
     */
    public function isIncludePage(int $pageUid): bool
    {
        $settings = $this->settingsService->getSettings();
        if (isset($settings['redirect']['includePageUids']) && $settings['redirect']['includePageUids'] !== '') {
            $includePids = $this->extendPidListByChildren(
                $settings['redirect']['includePageUids'],
                (int)$settings['redirect']['includePageUidsRecursionLevel']
            );
            $includePids = GeneralUtility::intExplode(',', $includePids, true);
        } else {
            $includePids = [];
        }
        return in_array($pageUid, $includePids, true);
    }

    /**
     * Returns, if the given page uid is configured as excluded from redirects
     *
     * @param int $pageUid
     * @return bool
     */
    public function isExcludePage(int $pageUid): bool
    {
        $settings = $this->settingsService->getSettings();
        if (isset($settings['redirect']['excludePageUids']) && $settings['redirect']['excludePageUids'] !== '') {
            $excludePids = $this->extendPidListByChildren(
                $settings['redirect']['excludePageUids'],
                (int)$settings['redirect']['excludePageUidsRecursionLevel']
            );
            $excludePids = GeneralUtility::intExplode(',', $excludePids, true);
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
    public function isAccessProtectedPageInRootline(array $rootline): bool
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

    /**
     * Find all ids from given ids and level
     *
     * @param string $pidList comma separated list of ids
     * @param int $recursive recursive levels
     * @return string comma separated list of ids
     */
    protected function extendPidListByChildren(string $pidList = '', int $recursive = 0): string
    {
        if ($recursive <= 0) {
            return $pidList;
        }

        $recursiveStoragePids = $pidList;
        $storagePids = GeneralUtility::intExplode(',', $pidList);
        foreach ($storagePids as $startPid) {
            if ($startPid >= 0) {
                $pids = (string)$this->getTreeList($startPid, $recursive, 0, 1);
                if (strlen($pids) > 0) {
                    $recursiveStoragePids .= ',' . $pids;
                }
            }
        }
        return StringUtility::uniqueList($recursiveStoragePids);
    }

    /**
     * Recursively fetch all descendants of a given page. Original function from TYPO3 core used, since
     * QueryGenerator is deprecated since #92080
     *
     * @param int $id uid of the page
     * @param int $depth
     * @param int $begin
     * @param string $permClause
     * @return string comma separated list of descendant pages
     */
    protected function getTreeList($id, $depth, $begin = 0, $permClause = '')
    {
        $depth = (int)$depth;
        $begin = (int)$begin;
        $id = (int)$id;
        if ($id < 0) {
            $id = abs($id);
        }
        if ($begin === 0) {
            $theList = $id;
        } else {
            $theList = '';
        }
        if ($id && $depth > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $queryBuilder->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('sys_language_uid', 0)
                )
                ->orderBy('uid');
            if ($permClause !== '') {
                $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix((string)$permClause));
            }
            $statement = $queryBuilder->execute();
            while ($row = $statement->fetch()) {
                if ($begin <= 0) {
                    $theList .= ',' . $row['uid'];
                }
                if ($depth > 1) {
                    $theSubList = $this->getTreeList($row['uid'], $depth - 1, $begin - 1, $permClause);
                    if (!empty($theList) && !empty($theSubList) && ($theSubList[0] !== ',')) {
                        $theList .= ',';
                    }
                    $theList .= $theSubList;
                }
            }
        }
        return $theList;
    }
}
