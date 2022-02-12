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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * This middleware redirects the current frontend user to a configured page if the user must change the password
 */
class ForcePasswordChangeRedirect implements MiddlewareInterface
{
    protected PageAccessService $pageAccessService;
    protected FrontendUserService $frontendUserService;
    protected TypoScriptFrontendController $controller;

    /**
     * ForcePasswordChangeRedirect constructor.
     * @param TypoScriptFrontendController|null $controller
     */
    public function __construct(
        PageAccessService $pageAccessService,
        FrontendUserService $frontendUserService,
        ?TypoScriptFrontendController $controller = null
    ) {
        $this->pageAccessService = $pageAccessService;
        $this->frontendUserService = $frontendUserService;
        $this->controller = $controller ?? $GLOBALS['TSFE'];
    }

    /**
     * Check if the user must change the password and redirect to configured PID
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return RedirectResponse|ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pageId = (int)$this->controller->id;

        // Early return if no frontend user available, page is excluded from redirect or user is not
        // forced to change the password
        if (!isset($this->controller->fe_user->user['uid']) ||
            !$this->frontendUserService->mustChangePassword($this->controller->fe_user->user) ||
            $this->pageAccessService->isExcludePage($this->controller->id)
        ) {
            return $handler->handle($request);
        }

        switch ($this->pageAccessService->getRedirectMode()) {
            case 'allAccessProtectedPages':
                $mustRedirect = $this->pageAccessService->isAccessProtectedPageInRootline($this->controller->rootLine);
                break;
            case 'includePageUids':
                $mustRedirect = $this->pageAccessService->isIncludePage($pageId);
                break;
            default:
                $mustRedirect = false;
        }

        if ($mustRedirect) {
            $this->controller->calculateLinkVars($request->getQueryParams());
            $parameter = $this->pageAccessService->getRedirectPid();
            if ($this->controller->type && MathUtility::canBeInterpretedAsInteger($pageId)) {
                $parameter .= ',' . $this->controller->type;
            }
            $url = GeneralUtility::makeInstance(ContentObjectRenderer::class, $this->controller)->typoLink_URL([
                'parameter' => $parameter,
                'addQueryString' => true,
                'addQueryString.' => ['exclude' => 'id'],
                // ensure absolute URL is generated when having a valid Site
                'forceAbsoluteUrl' => $request instanceof ServerRequestInterface
                    && $request->getAttribute('site') instanceof Site,
            ]);
            return new RedirectResponse($url, 307);
        }

        return $handler->handle($request);
    }
}
