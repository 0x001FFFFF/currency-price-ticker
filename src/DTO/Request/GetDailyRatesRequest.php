<?php

declare(strict_types=1);

namespace App\DTO\Request;

use App\Application\Validation\Constraint as AppAssert;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'GetDailyRatesRequest',
    description: 'Request parameters for daily currency rate endpoint',
    type: 'object',
    required: ['pair', 'date']
)]
final class GetDailyRatesRequest
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
        public string $pair = '',

        #[Assert\NotBlank(message: 'Date is required')]
        #[Assert\Date(message: 'Date must be in format Y-m-d')]
        #[Assert\LessThanOrEqual('today', message: 'Date cannot be in the future')]
        #[OA\Property(
            property: 'date',
            description: 'Date in YYYY-MM-DD format (no future dates allowed)',
            type: 'string',
            format: 'date',
            pattern: '^\d{4}-\d{2}-\d{2}$',
            example: '2025-09-07'
        )]
        public string $date = ''
    ) {
    }
}
