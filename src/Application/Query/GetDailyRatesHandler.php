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
final class GetDailyRatesHandler
{
    public function __construct(
        private readonly CurrencyRateRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
        private readonly CurrencyRateCacheService $cacheService
    ) {}

    public function __invoke(GetDailyRatesQuery $query): array
    {
        $pair = new CurrencyPair(...\explode('/', $query->pair));
        $date = new \DateTimeImmutable($query->date);
        $start = $date->setTime(0, 0, 0);
        $end = $date->setTime(23, 59, 59);

        $cacheKey = $this->cacheService->generateCacheKey($pair->toString(), 'day', $query->date);
        $cached = $this->cacheService->getCachedRates($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $rates = $this->repository->findByPairAndDateRange($pair->toString(), $start, $end);

        if (empty($rates)) {
            throw new NoDataFoundException($query->pair, $query->date);
        }

        $this->logger->info('Retrieved daily rates', [
            'pair' => $query->pair,
            'date' => $query->date,
            'count' => \count($rates)
        ]);

        $this->cacheService->cacheRates($cacheKey, $rates);

        return $rates;
    }
}
