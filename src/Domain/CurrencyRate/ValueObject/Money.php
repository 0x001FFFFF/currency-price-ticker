<?php

declare(strict_types=1);

namespace App\Domain\CurrencyRate\ValueObject;

final readonly class Money
{
    public function __construct(
        private float $amount,
        private string $currency = 'EUR'
    ) {
        $this->validate();
    }

    public static function fromFloat(float $amount, string $currency = 'EUR'): self
    {
        return new self($amount, $currency);
    }

    public static function fromString(string $amount, string $currency = 'EUR'): self
    {
        $floatAmount = (float) $amount;

        return new self($floatAmount, $currency);
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function formatForStorage(): string
    {
        return number_format($this->amount, 8, '.', '');
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->amount > $other->amount;
    }

    public function equals(self $other): bool
    {
        return abs($this->amount - $other->amount) < 0.00000001
            && $this->currency === $other->currency;
    }

    private function validate(): void
    {
        if ($this->amount < 0) {
            throw new \InvalidArgumentException('Money amount cannot be negative');
        }

        if (empty($this->currency)) {
            throw new \InvalidArgumentException('Currency cannot be empty');
        }
    }
}
