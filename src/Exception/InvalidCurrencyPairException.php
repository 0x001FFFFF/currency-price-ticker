<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Invalid currency pair exception.
 */
final class InvalidCurrencyPairException extends BusinessException
{
    protected string $errorCode = 'INVALID_CURRENCY_PAIR';

    public function __construct(string $pair)
    {
        parent::__construct(
            sprintf(
                'Currency pair "%s" is not supported. Allowed: EUR/BTC, EUR/ETH, EUR/LTC',
                $pair
            )
        );
    }
}
