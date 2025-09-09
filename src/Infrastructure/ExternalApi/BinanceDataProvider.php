<?php

declare(strict_types=1);

namespace App\Infrastructure\ExternalApi;

use App\Domain\CurrencyRate\Entity\CurrencyRate;
use App\Domain\CurrencyRate\ValueObject\CurrencyPair;
use App\Exception\InvalidResponseException;
use Psr\Log\LoggerInterface;

final class BinanceDataProvider
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param array<string, mixed> $rawData
     */
    public function transformToCurrencyRate(array $rawData, CurrencyPair $expectedPair): CurrencyRate
    {
        $this->validateResponseStructure($rawData, $expectedPair);

        $price = (float) $rawData['price'];
        $this->validatePrice($price);

        $this->logger->debug('Transforming Binance data to CurrencyRate', [
            'symbol' => $rawData['symbol'],
            'price' => $price,
            'expected_pair' => $expectedPair->toString(),
        ]);

        return new CurrencyRate(
            $expectedPair->toString(),
            number_format($price, 8, '.', ''),
            new \DateTimeImmutable(),
            'binance'
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateResponseStructure(array $data, CurrencyPair $expectedPair): void
    {
        if (! isset($data['symbol'], $data['price'])) {
            throw new InvalidResponseException('Missing required fields in Binance API response');
        }

        if ($data['symbol'] !== $expectedPair->toBinanceSymbol()) {
            throw new InvalidResponseException(
                "Symbol mismatch: expected {$expectedPair->toBinanceSymbol()}, got {$data['symbol']}"
            );
        }
    }

    private function validatePrice(float $price): void
    {
        if ($price <= 0) {
            throw new InvalidResponseException("Invalid price value: {$price}");
        }

        // Additional validation for extreme values (optional circuit breaker)
        if ($price > 1000000) { // Reasonable upper bound for EUR rates
            $this->logger->warning('Detected potentially extreme price value', [
                'price' => $price,
            ]);
        }
    }
}
