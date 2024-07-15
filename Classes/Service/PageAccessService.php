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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class with various functions for page redirects and page permission settings
 */
class PageAccessService
{
    /**
     * Returns the redirect mode
     */
    public function getRedirectMode(array $siteSettings): string
    {
        if (($siteSettings['redirect']['allAccessProtectedPages'] ?? false)) {
            $redirectMode = 'allAccessProtectedPages';
        } elseif (isset($siteSettings['redirect']['includePageUids']) &&
            $siteSettings['redirect']['includePageUids'] !== ''
        ) {
            $redirectMode = 'includePageUids';
        } else {
            $redirectMode = '';
        }
        return $redirectMode;
    }

    /**
     * Returns the configured redirect PID
     */
    public function getRedirectPid(array $siteSettings): int
    {
        if (!isset($siteSettings['changePasswordPid']) || (int)$siteSettings['changePasswordPid'] === 0) {
            throw new NoChangePasswordPidException(
                'Site setting fe_change_pwd.changePasswordPid is not defined or zero',
                1580040840
            );
        }
        return (int)$siteSettings['changePasswordPid'];
    }

    /**
     * Returns, if the given page uid is configured as included for redirects
     */
    public function isIncludePage(int $pageUid, array $siteSettings): bool
    {
        if (isset($siteSettings['redirect']['includePageUids']) &&
            $siteSettings['redirect']['includePageUids'] !== ''
        ) {
            $includePids = $this->extendPidListByChildren(
                $siteSettings['redirect']['includePageUids'],
                (int)$siteSettings['redirect']['includePageUidsRecursionLevel']
            );
            $includePids = GeneralUtility::intExplode(',', $includePids, true);
        } else {
            $includePids = [];
        }
        return in_array($pageUid, $includePids, true);
    }

    /**
     * Returns, if the given page uid is configured as excluded from redirects
     */
    public function isExcludePage(int $pageUid, array $siteSettings): bool
    {
        if (isset($siteSettings['redirect']['excludePageUids']) &&
            $siteSettings['redirect']['excludePageUids'] !== ''
        ) {
            $excludePids = $this->extendPidListByChildren(
                $siteSettings['redirect']['excludePageUids'],
                (int)$siteSettings['redirect']['excludePageUidsRecursionLevel']
            );
            $excludePids = GeneralUtility::intExplode(',', $excludePids, true);
        } else {
            $excludePids = [];
        }
        // Always add the changePasswordPid as exclude PID
        $excludePids[] = (int)$siteSettings['changePasswordPid'];
        return in_array($pageUid, $excludePids, true);
    }

    /**
     * Returns, if the there is an access protected page in the rootline respecting 'extendToSubpages' setting
     */
    public function isAccessProtectedPageInRootline(array $rootline): bool
    {
        $isAccessProtected = false;
        $loop = 0;
        foreach ($rootline as $rootlinePage) {
            $isPublic = ($rootlinePage['fe_group'] === '' || $rootlinePage['fe_group'] === '0');
            $extendToSubpages = (bool)$rootlinePage['extendToSubpages'];
            if (!$isPublic || ($extendToSubpages && $loop >= 1)) {
                $isAccessProtected = true;
                break;
            }
            $loop++;
        }
        return $isAccessProtected;
    }

    /**
     * Find all ids from given comma separated list of PIDs and level
     */
    protected function extendPidListByChildren(string $pidList = '', int $recursive = 0): string
    {
        if ($recursive <= 0) {
            return $pidList;
        }

        $recursiveStoragePids = $pidList;
        $storagePids = GeneralUtility::intExplode(',', $pidList);
        foreach ($storagePids as $startPid) {
            $pids = $this->getTreeList($startPid, $recursive);
            if ($pids !== '') {
                $recursiveStoragePids .= ',' . $pids;
            }
        }

        return $recursiveStoragePids;
    }

    /**
     * Recursively fetch all descendants of a given page
     */
    protected function getTreeList(int $id, int $depth, int $begin = 0, string $permClause = ''): string
    {
        if ($id < 0) {
            $id = (int)abs($id);
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
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('sys_language_uid', 0)
                )
                ->orderBy('uid');
            if ($permClause !== '') {
                $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($permClause));
            }
            $statement = $queryBuilder->executeQuery();
            while ($row = $statement->fetchAssociative()) {
                if ($begin <= 0) {
                    $theList .= ',' . $row['uid'];
                }
                if ($depth > 1) {
                    $theSubList = self::getTreeList((int)$row['uid'], $depth - 1, $begin - 1, $permClause);
                    if (!empty($theList) && !empty($theSubList) && ($theSubList[0] !== ',')) {
                        $theList .= ',';
                    }
                    $theList .= $theSubList;
                }
            }
        }

        return (string)$theList;
    }
}
