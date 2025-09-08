<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Exception\RateLimitExceededException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class ApiRateLimiter
{
    public function __construct(
        private readonly RateLimiterFactory $apiRateLimiterFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function checkRateLimit(Request $request): void
    {
        $identifier = $this->getClientIdentifier($request);
        $limiter = $this->apiRateLimiterFactory->create($identifier);

        $limit = $limiter->consume();

        if (! $limit->isAccepted()) {
            $this->logger->warning('Rate limit exceeded', [
                'client_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                'retry_after' => $limit->getRetryAfter()->getTimestamp(),
                'route' => $request->attributes->get('_route'),
            ]);

            throw new RateLimitExceededException(
                $limit->getRetryAfter()->getTimestamp()
            );
        }
    }

    private function getClientIdentifier(Request $request): string
    {
        $ip = $request->getClientIp() ?? 'unknown';
        $userAgent = $request->headers->get('User-Agent', 'unknown');

        return \hash('sha256', $ip . '|' . $userAgent);
    }
}
