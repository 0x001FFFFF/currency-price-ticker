<?php

declare(strict_types=1);

namespace App\Exception;

abstract class BusinessException extends CurrencyTickerException
{
    protected int $statusCode = 400;
}
