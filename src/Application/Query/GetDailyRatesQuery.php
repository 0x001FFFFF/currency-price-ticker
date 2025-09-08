<?php
declare(strict_types=1);

namespace App\Application\Query;

final readonly class GetDailyRatesQuery
{
    public function __construct(public string $pair, public string $date) {}
}
