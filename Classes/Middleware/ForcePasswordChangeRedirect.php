<?php

declare(strict_types=1);

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Derhansen\FeChangePwd\Middleware;

use Derhansen\FeChangePwd\Service\FrontendUserService;
use Derhansen\FeChangePwd\Service\PageAccessService;
use Derhansen\FeChangePwd\Service\SettingsService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * This middleware redirects the current frontend user to a configured page if the user must change the password
 */
class ForcePasswordChangeRedirect implements MiddlewareInterface
{
    public function __construct(
        protected PageAccessService $pageAccessService,
        protected FrontendUserService $frontendUserService,
        protected SettingsService $settingsService
    ) {}

    /**
     * Check if the user must change the password and redirect to configured PID
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $typoScriptFrontendController = $request->getAttribute('frontend.controller');
        $frontendUser = $request->getAttribute('frontend.user');
        $pageUid = $typoScriptFrontendController->id;

        // Early return, if no frontend user
        if (!isset($frontendUser->user['uid'])) {
            return $handler->handle($request);
        }

        $settings = $this->settingsService->getSettings($request);

        // Early return if page is excluded from redirect or user is not forced to change the password
        if (!$this->frontendUserService->mustChangePassword($frontendUser->user) ||
            $this->pageAccessService->isExcludePage($pageUid, $settings) ||
            ($request->getQueryParams()['tx_felogin_login']['action'] ?? '') === 'login'
        ) {
            return $handler->handle($request);
        }

        switch ($this->pageAccessService->getRedirectMode($settings)) {
            case 'allAccessProtectedPages':
                $mustRedirect = $this->pageAccessService->isAccessProtectedPageInRootline($typoScriptFrontendController->rootLine);
                break;
            case 'includePageUids':
                $mustRedirect = $this->pageAccessService->isIncludePage($pageUid, $settings);
                break;
            default:
                $mustRedirect = false;
        }

        if ($mustRedirect) {
            $typoScriptFrontendController->calculateLinkVars($request->getQueryParams());
            $parameter = $this->pageAccessService->getRedirectPid($settings);
            if (MathUtility::canBeInterpretedAsInteger($pageUid) &&
                $typoScriptFrontendController->getPageArguments()->getPageType()
            ) {
                $parameter .= ',' . $typoScriptFrontendController->getPageArguments()->getPageType();
            }
            $url = GeneralUtility::makeInstance(ContentObjectRenderer::class, $typoScriptFrontendController)->typoLink_URL([
                'parameter' => $parameter,
                'addQueryString' => true,
                'addQueryString.' => ['exclude' => 'id'],
                'forceAbsoluteUrl' => true,
            ]);
            return new RedirectResponse($url, 307);
        }

        return $handler->handle($request);
    }
}
