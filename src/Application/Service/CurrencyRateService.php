<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\CurrencyRate\Entity\CurrencyRate;
use App\Domain\CurrencyRate\Repository\CurrencyRateRepositoryInterface;
use Psr\Log\LoggerInterface;

class CurrencyRateService
{
    public function __construct(
        private readonly CurrencyRateRepositoryInterface $currencyRateRepository,
        private readonly LoggerInterface $logger
    ) {}

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
            'end_time' => $endTime->format('c')
        ]);

        $rates = $this->currencyRateRepository->findByPairAndDateRange(
            $pair,
            $startTime,
            $endTime
        );

        return [
            'data' => array_map(fn(CurrencyRate $rate) => [
                'pair' => $rate->getPair(),
                'rate' => $rate->getRate(),
                'timestamp' => $rate->getTimestamp()->format('Y-m-d H:i:s')
            ], $rates),
            'count' => count($rates),
            'start_time' => $rates ? $rates[0]->getTimestamp() : null,
            'end_time' => $rates ? end($rates)->getTimestamp() : null
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDailyRates(string $pair, string $date): array
    {
        $targetDate = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$targetDate) {
            throw new \InvalidArgumentException('Invalid date format. Expected Y-m-d');
        }

        $startTime = $targetDate->setTime(0, 0, 0);
        $endTime = (clone $targetDate)->setTime(23, 59, 59);

        $this->logger->info('Fetching daily rates', [
            'pair' => $pair,
            'date' => $date,
            'start_time' => $startTime->format('c'),
            'end_time' => $endTime->format('c')
        ]);

        $rates = $this->currencyRateRepository->findByPairAndDateRange(
            $pair,
            $startTime,
            $endTime
        );

        return [
            'data' => array_map(fn(CurrencyRate $rate) => [
                'pair' => $rate->getPair(),
                'rate' => $rate->getRate(),
                'timestamp' => $rate->getTimestamp()->format('Y-m-d H:i:s')
            ], $rates),
            'count' => count($rates),
            'start_time' => $rates ? $rates[0]->getTimestamp() : null,
            'end_time' => $rates ? end($rates)->getTimestamp() : null
        ];
    }
}
