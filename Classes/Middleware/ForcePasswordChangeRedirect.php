<?php
declare(strict_types=1);
namespace Derhansen\FeChangePwd\Middleware;

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

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
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * This middleware redirects the current frontend user to a configured page if the user must change the password
 */
class ForcePasswordChangeRedirect implements MiddlewareInterface
{
    /**
     * @var TypoScriptFrontendController
     */
    protected $controller;

    /**
     * ForcePasswordChangeRedirect constructor.
     * @param TypoScriptFrontendController|null $controller
     */
    public function __construct(TypoScriptFrontendController $controller = null)
    {
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
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $pageAccessService = $objectManager->get(PageAccessService::class);
        $frontendUserService = $objectManager->get(FrontendUserService::class);

        // Early return if no frontend user available, page is excluded from redirect or user is not
        // forced to change the password
        if (!isset($this->controller->fe_user->user['uid']) ||
            !$frontendUserService->mustChangePassword($this->controller->fe_user->user) ||
            $pageAccessService->isExcludePage($this->controller->id)
        ) {
            return $handler->handle($request);
        }

        switch ($pageAccessService->getRedirectMode()) {
            case 'allAccessProtectedPages':
                $mustRedirect = $pageAccessService->isAccessProtectedPageInRootline($this->controller->rootLine);
                break;
            case 'includePageUids':
                $mustRedirect = $pageAccessService->isIncludePage($this->controller->id);
                break;
            default:
                $mustRedirect = false;
        }

        if ($mustRedirect) {
            $this->controller->calculateLinkVars($request->getQueryParams());
            $parameter = $pageAccessService->getRedirectPid();
            if ($this->controller->type && MathUtility::canBeInterpretedAsInteger($this->controller->type)) {
                $parameter .= ',' . $this->controller->type;
            }
            $url = GeneralUtility::makeInstance(ContentObjectRenderer::class, $this->controller)->typoLink_URL([
                'parameter' => $parameter,
                'addQueryString' => true,
                'addQueryString.' => ['exclude' => 'id'],
                // ensure absolute URL is generated when having a valid Site
                'forceAbsoluteUrl' => $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface
                    && $GLOBALS['TYPO3_REQUEST']->getAttribute('site') instanceof Site
            ]);
            return new RedirectResponse($url, 307);
        }

        return $handler->handle($request);
    }
}
