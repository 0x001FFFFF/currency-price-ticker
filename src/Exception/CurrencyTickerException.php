<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Base exception for Currency Ticker application.
 *
 * Follows Memory Bank error handling patterns.
 */
abstract class CurrencyTickerException extends \Exception
{
    protected int $statusCode = 500;
    protected string $errorCode = 'UNKNOWN_ERROR';

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiResponse(): array
    {
        return [
            'error_code' => $this->getErrorCode(),
            'message' => $this->getMessage(),
            'status_code' => $this->getStatusCode(),
            'timestamp' => (new \DateTime())->format('c'),
        ];
    }
}
