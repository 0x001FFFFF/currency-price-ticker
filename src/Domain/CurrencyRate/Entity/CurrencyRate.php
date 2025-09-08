<?php

declare(strict_types=1);

namespace App\Domain\CurrencyRate\Entity;

use App\Infrastructure\Repository\CurrencyRateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CurrencyRateRepository::class)]
#[ORM\Table(name: 'currency_rates')]
#[ORM\UniqueConstraint(name: 'uk_pair_timestamp', columns: ['pair', 'timestamp'])]
#[ORM\Index(columns: ['pair', 'timestamp'], name: 'idx_pair_timestamp')]
#[ORM\Index(columns: ['timestamp'], name: 'idx_timestamp')]
class CurrencyRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore-next-line property.unusedType */
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private string $pair;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 8)]
    private string $rate;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $timestamp;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $source = 'binance';

    public function __construct(
        string $pair,
        string $rate,
        \DateTimeImmutable $timestamp,
        ?string $source = 'binance'
    ) {
        $this->pair = $pair;
        $this->rate = $rate;
        $this->timestamp = $timestamp;
        $this->source = $source;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPair(): string
    {
        return $this->pair;
    }

    public function getRate(): string
    {
        return $this->rate;
    }

    public function getRateAsFloat(): float
    {
        return (float) $this->rate;
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'pair' => $this->pair,
            'rate' => $this->getRateAsFloat(),
            'timestamp' => $this->timestamp->format('c'),
        ];
    }

    public function isStale(int $maxAgeSeconds = 300): bool
    {
        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $this->timestamp->getTimestamp();
        return $diff > $maxAgeSeconds;
    }
}
