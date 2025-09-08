<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Rate limit exceeded exception.
 */
final class RateLimitExceededException extends BusinessException
{
    protected string $errorCode = 'RATE_LIMIT_EXCEEDED';
    protected int $statusCode = 429;
    private int $retryAfter;

    public function __construct(int $retryAfter = 60)
    {
        parent::__construct('Rate limit exceeded. Please try again later.');
        $this->retryAfter = $retryAfter;
    }
    
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
