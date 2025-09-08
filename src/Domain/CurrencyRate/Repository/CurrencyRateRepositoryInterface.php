<?php

declare(strict_types=1);

namespace App\Domain\CurrencyRate\Repository;

use App\Domain\CurrencyRate\Entity\CurrencyRate;
use App\Domain\CurrencyRate\ValueObject;

interface CurrencyRateRepositoryInterface
{
    public function findById(int $id): ?CurrencyRate;

    public function findLatestByPair(string $pair): ?CurrencyRate;

    /**
     * @return array<int, CurrencyRate>
     */
    public function findByPairAndDateRange(
        string $pair,
        \DateTimeInterface $start,
        \DateTimeInterface $end
    ): array;

    public function save(CurrencyRate $rate): void;

    /**
     * @param array<CurrencyRate> $rates
     */
    public function saveBatch(array $rates): void;
}
