<?php

declare(strict_types=1);

namespace App\Application\Query;

use App\Domain\CurrencyRate\Repository\CurrencyRateRepositoryInterface;
use App\Domain\CurrencyRate\ValueObject\CurrencyPair;
use App\Exception\NoDataFoundException;
use App\Infrastructure\Cache\CurrencyRateCacheService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

// use fully-qualified core functions for static analysis

#[AsMessageHandler]
final class GetLast24HoursRatesHandler
{
    public function __construct(
        private readonly CurrencyRateRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
        private readonly CurrencyRateCacheService $cacheService
    ) {
    }

    /** @return array<\App\Domain\CurrencyRate\Entity\CurrencyRate> */
    public function __invoke(GetLast24HoursRatesQuery $query): array
    {
        $pair = new CurrencyPair(...\explode('/', $query->pair));
        $end = new \DateTimeImmutable();
        $start = $end->sub(new \DateInterval('P1D'));

        $cacheKey = $this->cacheService->generateCacheKey($pair->toString(), '24h');
        $cached = $this->cacheService->getCachedRates($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $rates = $this->repository->findByPairAndDateRange($pair->toString(), $start, $end);

        if (empty($rates)) {
            throw new NoDataFoundException($query->pair, 'last 24 hours');
        }

        $this->logger->info('Retrieved 24h rates', [
            'pair' => $query->pair,
            'count' => \count($rates),
        ]);

        $this->cacheService->cacheRates($cacheKey, $rates);

        return $rates;
    }
}
