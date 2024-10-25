<?php

declare(strict_types=1);

namespace Derhansen\FeChangePwd\Event;

use Psr\Http\Message\ServerRequestInterface;

/**
 * This event is triggered before the change password redirect URL is created. Listeners can use this event
 * to change the parameters and the redirectPid used to construct the redirect URL
 */
final class ModifyRedirectUrlParameterEvent
{
    public function __construct(
        private readonly ServerRequestInterface $request,
        private int $redirectPid,
        private array $parameter
    ) {}

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getRedirectPid(): int
    {
        return $this->redirectPid;
    }

    public function setRedirectPid(int $redirectPid): void
    {
        $this->redirectPid = $redirectPid;
    }

    public function getParameter(): array
    {
        return $this->parameter;
    }

    public function setParameter(array $parameter): void
    {
        $this->parameter = $parameter;
    }
}
