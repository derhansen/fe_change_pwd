<?php
declare(strict_types = 1);
namespace Derhansen\FeChangePwd\Hooks;

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Derhansen\FeChangePwd\Service\FrontendUserService;
use Derhansen\FeChangePwd\Service\PageAccessService;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class TsfeHooks
 */
class TsfeHooks
{
    /**
     * Checks if a password change should be forced
     *
     * @param array $params
     * @param $parentObject
     * @return void
     */
    public function checkForcePasswordChange($params, $parentObject)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $pageAccessService = $objectManager->get(PageAccessService::class);
        $frontendUserService = $objectManager->get(FrontendUserService::class);

        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = $params['pObj'];

        // Early return if no frontend user available, page is excluded from redirect or user is not
        // forced to change the password
        if (!isset($tsfe->fe_user->user['uid']) ||
            $pageAccessService->isExcludePage($tsfe->id) ||
            !$frontendUserService->mustChangePassword($tsfe->fe_user->user)
        ) {
            return;
        }

        switch ($pageAccessService->getRedirectMode()) {
            case 'allAccessProtectedPages':
                $mustRedirect = $pageAccessService->isAccessProtectedPageInRootline($tsfe->rootLine);
                break;
            case 'includePageUids':
                $mustRedirect = $pageAccessService->isIncludePage($tsfe->id);
                break;
            default:
                $mustRedirect = false;
        }

        if ($mustRedirect) {
            $tsfe->calculateLinkVars();
            $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $parameter = $pageAccessService->getRedirectPid();
            $type = GeneralUtility::_GET('type');
            if ($type && MathUtility::canBeInterpretedAsInteger($type)) {
                $parameter .= ',' . $type;
            }
            $url = $cObj->typoLink_URL(['parameter' => $parameter, 'addQueryString' => true,
                'addQueryString.' => ['exclude' => 'id']]);
            HttpUtility::redirect($url, HttpUtility::HTTP_STATUS_307);
        }
    }
}
