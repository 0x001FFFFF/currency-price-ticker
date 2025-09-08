<?php

declare(strict_types=1);

namespace App\DTO\Response;

use OpenApi\Attributes as OA;

/**
 * Response DTO for API serialization.
 * Follows Memory Bank DTO patterns for clean API responses.
 */
#[OA\Schema(
    schema: 'CurrencyRateResponseDTO',
    description: 'Single currency rate data point',
    type: 'object'
)]
final readonly class CurrencyRateResponseDTO
{
    public function __construct(
        #[OA\Property(
            property: 'pair',
            description: 'Currency pair in format BASE/QUOTE',
            type: 'string',
            enum: ['EUR/BTC', 'EUR/ETH', 'EUR/LTC'],
            example: 'EUR/BTC'
        )]
        public string $pair,
        #[OA\Property(
            property: 'rate',
            description: 'Exchange rate value',
            type: 'number',
            format: 'float',
            minimum: 0,
            example: 45678.90
        )]
        public float $rate,
        #[OA\Property(
            property: 'timestamp',
            description: 'Rate timestamp in ISO 8601 format (RFC 3339)',
            type: 'string',
            format: 'date-time',
            example: '2025-09-08T10:30:00Z'
        )]
        public string $timestamp
    ) {
    }

    public static function fromEntity(\App\Domain\CurrencyRate\Entity\CurrencyRate $rate): self
    {
        return new self(
            pair: $rate->getPair(),
            rate: $rate->getRateAsFloat(),
            timestamp: $rate->getTimestamp()->format('c')
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'pair' => $this->pair,
            'rate' => $this->rate,
            'timestamp' => $this->timestamp,
        ];
    }
}
