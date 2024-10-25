<?php

declare(strict_types=1);

namespace Derhansen\FeChangePwd\Event;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * This event is triggered before the response for the updateAction is returned. Listeners can use this event
 * to modify the response object (e.g. replace it with a custom response)
 */
final class ModifyUpdatePasswordResponseEvent
{
    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly array $settings,
        private ResponseInterface $response
    ) {}

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }
}
