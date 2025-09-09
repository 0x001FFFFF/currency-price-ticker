<?php

declare(strict_types=1);

namespace App\Domain\CurrencyRate\ValueObject;

use App\Exception\InvalidCurrencyPairException;

final readonly class CurrencyPair
{
    private const SUPPORTED_PAIRS = [
        'EUR/BTC' => 'BTCEUR',
        'EUR/ETH' => 'ETHEUR',
        'EUR/LTC' => 'LTCEUR',
    ];

    public function __construct(
        private string $baseCurrency,
        private string $quoteCurrency
    ) {
        $this->validateFormat();
    }

    public function toString(): string
    {
        return sprintf('%s/%s', $this->baseCurrency, $this->quoteCurrency);
    }

    public function toBinanceSymbol(): string
    {
        $pairString = $this->toString();
        if (! isset(self::SUPPORTED_PAIRS[$pairString])) {
            throw new InvalidCurrencyPairException("Unsupported pair for Binance: {$pairString}");
        }

        return self::SUPPORTED_PAIRS[$pairString];
    }

    public function getBaseCurrency(): string
    {
        return $this->baseCurrency;
    }

    public function getQuoteCurrency(): string
    {
        return $this->quoteCurrency;
    }

    public function equals(self $other): bool
    {
        return $this->toString() === $other->toString();
    }

    private function validateFormat(): void
    {
        if (strlen($this->baseCurrency) !== 3 || strlen($this->quoteCurrency) !== 3) {
            throw new InvalidCurrencyPairException(
                'Currency codes must be exactly 3 characters long'
            );
        }

        if (! in_array($this->toString(), array_keys(self::SUPPORTED_PAIRS), true)) {
            throw new InvalidCurrencyPairException(
                sprintf(
                    'Currency pair "%s" is not supported. Supported pairs: %s',
                    $this->toString(),
                    implode(', ', array_keys(self::SUPPORTED_PAIRS))
                )
            );
        }
    }
}
