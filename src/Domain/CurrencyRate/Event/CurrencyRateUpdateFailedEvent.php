<?php

declare(strict_types=1);

namespace App\Domain\CurrencyRate\Event;

final readonly class CurrencyRateUpdateFailedEvent
{
    /**
     * @param array<string, mixed>|null $context
     */
    public function __construct(
        private string $pair,
        private string $errorMessage,
        private string $errorCode,
        private \DateTimeImmutable $occurredAt,
        private ?array $context = null
    ) {
    }

    public function getPair(): string
    {
        return $this->pair;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }
}
