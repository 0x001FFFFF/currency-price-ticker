<?php

declare(strict_types=1);

namespace App\Application\Query;

final readonly class GetLast24HoursRatesQuery
{
    public function __construct(public string $pair)
    {
    }
}
