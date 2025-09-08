<?php

declare(strict_types=1);

namespace App\Domain\CurrencyRate\ValueObject;

/**
 * Money Value Object for handling monetary amounts.
 */
final readonly class Money
{
    public function __construct(
        private float $amount,
        private string $currency = 'EUR'
    ) {
        $this->validate();
    }
    
    public function getAmount(): float
    {
        return $this->amount;
    }
    
    public function getCurrency(): string
    {
        return $this->currency;
    }
    
    public static function fromString(string $amount, string $currency = 'EUR'): self
    {
        return new self((float) $amount, $currency);
    }
    
    private function validate(): void
    {
        if ($this->amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }
        
        if (empty($this->currency)) {
            throw new \InvalidArgumentException('Currency is required');
        }
    }
}