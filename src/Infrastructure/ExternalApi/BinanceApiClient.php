<?php

declare(strict_types=1);

namespace App\Infrastructure\ExternalApi;

use App\Exception\ExternalApiException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class BinanceApiClient
{
    private const API_BASE_URL = 'https://api.binance.com';
    private const MAX_RETRIES = 3;
    private const RETRY_BASE_DELAY_MS = 1000;
    private const REQUEST_TIMEOUT = 10.0;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchRateData(string $symbol): array
    {
        $this->logger->debug('Fetching rate data from Binance API', [
            'symbol' => $symbol,
            'endpoint' => self::API_BASE_URL . '/api/v3/ticker/price',
        ]);

        return $this->executeWithRetry(function () use ($symbol) {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/api/v3/ticker/price', [
                'query' => ['symbol' => $symbol],
                'timeout' => self::REQUEST_TIMEOUT,
                'headers' => [
                    'User-Agent' => 'CurrencyPriceTicker/1.0',
                    'Accept' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new ExternalApiException(
                    "HTTP {$response->getStatusCode()}: {$response->getContent(false)}"
                );
            }

            return $response->toArray();
        }, $symbol);
    }

    public function healthCheck(): bool
    {
        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/api/v3/ping', [
                'timeout' => 5.0,
            ]);

            return $response->getStatusCode() === 200;

        } catch (\Throwable $exception) {
            $this->logger->warning('Binance API health check failed', [
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function executeWithRetry(callable $operation, string $context): mixed
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < self::MAX_RETRIES) {
            try {
                return $operation();

            } catch (\Throwable $exception) {
                $lastException = $exception;
                $attempts++;

                if ($attempts >= self::MAX_RETRIES) {
                    break;
                }

                $delayMs = self::RETRY_BASE_DELAY_MS * (2 ** ($attempts - 1));
                usleep($delayMs * 1000);

                $this->logger->warning('Retrying Binance API request', [
                    'context' => $context,
                    'attempt' => $attempts,
                    'delay_ms' => $delayMs,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        throw new ExternalApiException(
            "Request failed after {$attempts} attempts: {$lastException->getMessage()}",
            0,
            $lastException
        );
    }
}
