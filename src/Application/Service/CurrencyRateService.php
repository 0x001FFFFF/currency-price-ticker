<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Command\UpdateResult;
use App\Domain\CurrencyRate\Entity\CurrencyRate;
use App\Domain\CurrencyRate\Event\CurrencyRateUpdatedEvent;
use App\Domain\CurrencyRate\Event\CurrencyRateUpdateFailedEvent;
use App\Domain\CurrencyRate\Repository\CurrencyRateRepositoryInterface;
use App\Infrastructure\Cache\CurrencyRateCacheService;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class CurrencyRateService
{
    public function __construct(
        private readonly CurrencyRateRepositoryInterface $currencyRateRepository,
        private readonly BinanceApiService $binanceApiService,
        private readonly CurrencyRateCacheService $cacheService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger
    ) {
    }

    public function updateAllRates(bool $force = false): UpdateResult
    {
        $supportedPairs = ['EUR/BTC', 'EUR/ETH', 'EUR/LTC'];

        return $this->updateSpecificRates($supportedPairs, $force);
    }

    /**
     * @param array<string> $pairs
     */
    public function updateSpecificRates(array $pairs, bool $force = false): UpdateResult
    {
        $startTime = microtime(true);
        $result = new UpdateResult();

        $this->logger->info('Starting currency rates update', [
            'pairs' => $pairs,
            'forced' => $force,
            'timestamp' => (new \DateTimeImmutable())->format('c'),
        ]);

        foreach ($pairs as $pairString) {
            try {
                $rate = $this->binanceApiService->fetchRateForPair($pairString);

                if (! $force && $this->isDuplicateRate($rate)) {
                    $this->logger->debug('Skipping duplicate rate', [
                        'pair' => $pairString,
                        'rate' => $rate->getRateAsFloat(),
                    ]);

                    continue;
                }

                $this->currencyRateRepository->save($rate);
                $this->cacheService->invalidateForPair($pairString);

                $result->addSuccess($pairString);

                $this->eventDispatcher->dispatch(
                    new CurrencyRateUpdatedEvent($rate, new \DateTimeImmutable())
                );

                $this->logger->info('Successfully updated rate', [
                    'pair' => $pairString,
                    'rate' => $rate->getRateAsFloat(),
                    'source' => $rate->getSource(),
                ]);

            } catch (\Throwable $exception) {
                $result->addError($pairString, $exception->getMessage());

                $this->eventDispatcher->dispatch(
                    new CurrencyRateUpdateFailedEvent(
                        $pairString,
                        $exception->getMessage(),
                        $exception::class,
                        new \DateTimeImmutable(),
                        ['trace' => $exception->getTraceAsString()]
                    )
                );

                $this->logger->error('Failed to update rate', [
                    'pair' => $pairString,
                    'error' => $exception->getMessage(),
                    'exception_class' => $exception::class,
                ]);
            }
        }

        $duration = microtime(true) - $startTime;
        $this->logger->info('Currency rates update completed', [
            'duration_ms' => round($duration * 1000, 2),
            'success_count' => $result->getSuccessCount(),
            'updated_count' => $result->getUpdatedCount(),
            'error_count' => $result->getErrorCount(),
        ]);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function getLast24HoursRates(string $pair): array
    {
        $endTime = new \DateTime();
        $startTime = (clone $endTime)->sub(new \DateInterval('P1D'));

        $this->logger->info('Fetching last 24h rates', [
            'pair' => $pair,
            'start_time' => $startTime->format('c'),
            'end_time' => $endTime->format('c'),
        ]);

        $rates = $this->currencyRateRepository->findByPairAndDateRange(
            $pair,
            $startTime,
            $endTime
        );

        return [
            'data' => array_map(fn (CurrencyRate $rate) => [
                'pair' => $rate->getPair(),
                'rate' => $rate->getRateAsFloat(),
                'timestamp' => $rate->getTimestamp()->format('Y-m-d H:i:s'),
            ], $rates),
            'count' => count($rates),
            'start_time' => $rates ? $rates[0]->getTimestamp() : null,
            'end_time' => $rates ? end($rates)->getTimestamp() : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDailyRates(string $pair, string $date): array
    {
        $targetDate = \DateTime::createFromFormat('Y-m-d', $date);
        if (! $targetDate) {
            throw new \InvalidArgumentException('Invalid date format. Expected Y-m-d');
        }

        $startTime = $targetDate->setTime(0, 0, 0);
        $endTime = (clone $targetDate)->setTime(23, 59, 59);

        $this->logger->info('Fetching daily rates', [
            'pair' => $pair,
            'date' => $date,
            'start_time' => $startTime->format('c'),
            'end_time' => $endTime->format('c'),
        ]);

        $rates = $this->currencyRateRepository->findByPairAndDateRange(
            $pair,
            $startTime,
            $endTime
        );

        return [
            'data' => array_map(fn (CurrencyRate $rate) => [
                'pair' => $rate->getPair(),
                'rate' => $rate->getRateAsFloat(),
                'timestamp' => $rate->getTimestamp()->format('Y-m-d H:i:s'),
            ], $rates),
            'count' => count($rates),
            'start_time' => $rates ? $rates[0]->getTimestamp() : null,
            'end_time' => $rates ? end($rates)->getTimestamp() : null,
        ];
    }

    private function isDuplicateRate(CurrencyRate $rate): bool
    {
        $existing = $this->currencyRateRepository->findLatestByPair($rate->getPair());

        if (! $existing) {
            return false;
        }

        // Consider it duplicate if rate is same and timestamp is within 1 minute
        $timeDiff = abs($rate->getTimestamp()->getTimestamp() - $existing->getTimestamp()->getTimestamp());
        $rateDiff = abs($rate->getRateAsFloat() - $existing->getRateAsFloat());

        return $timeDiff < 60 && $rateDiff < 0.00000001;
    }
}
