<?php

declare(strict_types=1);

namespace App\DTO\Request;

use App\Application\Validation\Constraint as AppAssert;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'GetLast24HoursRatesRequest',
    description: 'Request parameters for last 24 hours currency rate endpoint',
    type: 'object',
    required: ['pair']
)]
final class GetLast24HoursRatesRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Currency pair is required')]
        #[AppAssert\SupportedCurrencyPair]
        #[OA\Property(
            property: 'pair',
            description: 'Currency pair in format BASE/QUOTE. Only EUR-based pairs are supported.',
            type: 'string',
            enum: ['EUR/BTC', 'EUR/ETH', 'EUR/LTC'],
            example: 'EUR/BTC'
        )]
        public string $pair = ''
    ) {
    }
}
