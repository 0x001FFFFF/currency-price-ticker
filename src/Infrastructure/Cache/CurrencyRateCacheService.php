<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

final class CurrencyRateCacheService
{
    private const CACHE_TTL = 30;
    private const CACHE_PREFIX = 'currency_rate_';

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger
    ) {}

    public function getCachedRates(string $cacheKey): ?array
    {
        try {
            $item = $this->cache->getItem(self::CACHE_PREFIX . $cacheKey);

            if ($item->isHit()) {
                $this->logger->debug('Cache hit for rates', ['key' => $cacheKey]);
                /** @var array|null $value */
                $value = $item->get();
                return $value;
            }

            return null;
        } catch (\Throwable $e) {
            $this->logger->warning('Cache retrieval failed', [
                'key' => $cacheKey,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    public function cacheRates(string $cacheKey, array $rates): void
    {
        try {
            $item = $this->cache->getItem(self::CACHE_PREFIX . $cacheKey);
            $item->set($rates);
            $item->expiresAfter(self::CACHE_TTL);

            $this->cache->save($item);

            $this->logger->debug('Rates cached successfully', [
                'key' => $cacheKey,
                'count' => \count($rates),
                'ttl' => self::CACHE_TTL
            ]);
        } catch (\Throwable $e) {
            $this->logger->warning('Cache storage failed', [
                'key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function generateCacheKey(string $pair, string $period, ?string $date = null): string
    {
        $keyParts = [$pair, $period];

        if ($date) {
            $keyParts[] = $date;
        }

        return \hash('md5', \implode('_', $keyParts));
    }
}


