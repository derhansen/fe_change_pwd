<?php

declare(strict_types=1);

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Derhansen\FeChangePwd\Middleware;

use Derhansen\FeChangePwd\Event\ModifyRedirectUrlParameterEvent;
use Derhansen\FeChangePwd\Service\FrontendUserService;
use Derhansen\FeChangePwd\Service\PageAccessService;
use Derhansen\FeChangePwd\Service\SettingsService;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * This middleware redirects the current frontend user to a configured page if the user must change the password
 */
class ForcePasswordChangeRedirect implements MiddlewareInterface
{
    public function __construct(
        protected PageAccessService $pageAccessService,
        protected FrontendUserService $frontendUserService,
        protected SettingsService $settingsService,
        protected EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * Check if the user must change the password and redirect to configured PID
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pageInformation = $request->getAttribute('frontend.page.information');
        $frontendUser = $request->getAttribute('frontend.user');
        $pageUid = $pageInformation->getId();

        // Early return, if no frontend user
        if (!isset($frontendUser->user['uid'])) {
            return $handler->handle($request);
        }

        $siteSettings = $this->settingsService->getSiteSettings($request);

        // Early return if page is excluded from redirect or user is not forced to change the password
        if (!$this->frontendUserService->mustChangePassword($request, $frontendUser->user) ||
            $this->pageAccessService->isExcludePage($pageUid, $siteSettings) ||
            ($request->getQueryParams()['tx_felogin_login']['action'] ?? '') === 'login'
        ) {
            return $handler->handle($request);
        }

        switch ($this->pageAccessService->getRedirectMode($siteSettings)) {
            case 'allAccessProtectedPages':
                $mustRedirect = $this->pageAccessService->isAccessProtectedPageInRootline(
                    $pageInformation->getLocalRootLine()
                );
                break;
            case 'includePageUids':
                $mustRedirect = $this->pageAccessService->isIncludePage($pageUid, $siteSettings);
                break;
            default:
                $mustRedirect = false;
        }

        if ($mustRedirect) {
            $redirectPid = $this->pageAccessService->getRedirectPid($siteSettings);

            /** @var SiteLanguage $language */
            $language = $request->getAttribute('language');

            /** @var Site $site */
            $site = $request->getAttribute('site');
            $router = $site->getRouter();

            $event = $this->eventDispatcher->dispatch(new ModifyRedirectUrlParameterEvent(
                $request,
                $redirectPid,
                ['_language' => $language]
            ));

            $url = (string)$router->generateUri($event->getRedirectPid(), $event->getParameter());
            return new RedirectResponse($url, 307);
        }

        return $handler->handle($request);
    }
}
