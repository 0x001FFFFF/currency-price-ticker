<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Infrastructure exceptions.
 */
abstract class InfrastructureException extends CurrencyTickerException
{
    protected int $statusCode = 500;
}
