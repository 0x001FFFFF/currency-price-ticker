<?php

declare(strict_types=1);

namespace App\Domain\CurrencyRate\ValueObject;

use App\Exception\InvalidCurrencyPairException;

final readonly class CurrencyPair
{
    public function __construct(
        private string $baseCurrency,
        private string $quoteCurrency
    ) {
        $this->validateFormat();
    }

    public function toString(): string
    {
        return sprintf('%s%s', $this->baseCurrency, $this->quoteCurrency);
    }

    private function validateFormat(): void
    {
        if (strlen($this->baseCurrency) !== 3 || strlen($this->quoteCurrency) !== 3) {
            throw new InvalidCurrencyPairException(
                'Invalid currency format in pair. Each currency must be 3 characters long.'
            );
        }
    }
}
