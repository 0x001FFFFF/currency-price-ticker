<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\CurrencyRate\Entity\CurrencyRate;
use App\Domain\CurrencyRate\ValueObject\CurrencyPair;
use App\Infrastructure\ExternalApi\BinanceApiClient;
use App\Infrastructure\ExternalApi\BinanceDataProvider;
use Psr\Log\LoggerInterface;

class BinanceApiService
{
    public function __construct(
        private readonly BinanceApiClient $apiClient,
        private readonly BinanceDataProvider $dataProvider,
        private readonly LoggerInterface $logger
    ) {
    }

    public function fetchRateForPair(string $pairString): CurrencyRate
    {
        $this->logger->info('Fetching rate for pair', ['pair' => $pairString]);

        try {
            $currencyPair = new CurrencyPair(...explode('/', $pairString));
            $rawData = $this->apiClient->fetchRateData($currencyPair->toBinanceSymbol());

            return $this->dataProvider->transformToCurrencyRate($rawData, $currencyPair);

        } catch (\Throwable $exception) {
            $this->logger->error('Failed to fetch rate for pair', [
                'pair' => $pairString,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @return array<CurrencyRate>
     */
    public function fetchAllSupportedRates(): array
    {
        $supportedPairs = ['EUR/BTC', 'EUR/ETH', 'EUR/LTC'];
        $rates = [];

        foreach ($supportedPairs as $pairString) {
            try {
                $rates[] = $this->fetchRateForPair($pairString);
            } catch (\Throwable $exception) {
                $this->logger->error('Failed to fetch rate during batch operation', [
                    'pair' => $pairString,
                    'error' => $exception->getMessage(),
                ]);
                // Continue with other pairs instead of failing completely
            }
        }

        return $rates;
    }

    public function isHealthy(): bool
    {
        try {
            return $this->apiClient->healthCheck();
        } catch (\Throwable) {
            return false;
        }
    }
}
