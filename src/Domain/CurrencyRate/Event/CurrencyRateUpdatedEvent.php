<?php

declare(strict_types=1);

namespace App\Domain\CurrencyRate\Event;

use App\Domain\CurrencyRate\Entity\CurrencyRate;

final readonly class CurrencyRateUpdatedEvent
{
    public function __construct(
        private CurrencyRate $currencyRate,
        private \DateTimeImmutable $occurredAt
    ) {
    }

    public function getCurrencyRate(): CurrencyRate
    {
        return $this->currencyRate;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getPair(): string
    {
        return $this->currencyRate->getPair();
    }

    public function getRate(): float
    {
        return $this->currencyRate->getRateAsFloat();
    }

    public function getSource(): ?string
    {
        return $this->currencyRate->getSource();
    }
}
