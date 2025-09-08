<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Application\Service\ApiRateLimiter;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class RateLimitListener
{
    public function __construct(
        private readonly ApiRateLimiter $rateLimiter
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (! \str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $this->rateLimiter->checkRateLimit($request);
    }
}
