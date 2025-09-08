<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Application\Query\GetDailyRatesQuery;
use App\Application\Query\GetLast24HoursRatesQuery;
use App\DTO\Request\GetDailyRatesRequest;
use App\DTO\Request\GetLast24HoursRatesRequest;
use App\DTO\Response\CurrencyRateResponseDTO;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/rates')]
#[OA\Tag(name: 'Currency Rates')]
final class CurrencyRateController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $queryBus)
    {
        $this->messageBus = $queryBus;
    }

    #[Route('/last-24h', methods: ['GET'])]
    #[OA\Get(
        path: '/api/rates/last-24h',
        summary: 'Get currency rates for the last 24 hours',
        description: 'Retrieves cryptocurrency exchange rates for a specified currency pair over the last 24 hours. Data points are collected every 5 minutes, providing high-resolution time-series data for analysis and visualization.',
        tags: ['Currency Rates']
    )]
    #[OA\Parameter(
        name: 'pair',
        description: 'Currency pair in format BASE/QUOTE. Only EUR-based pairs are supported.',
        in: 'query',
        required: true,
        schema: new OA\Schema(
            type: 'string',
            enum: ['EUR/BTC', 'EUR/ETH', 'EUR/LTC'],
            example: 'EUR/BTC'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful response with currency rates data',
        content: new OA\JsonContent(
            ref: '#/components/schemas/CurrencyRatesResponse'
        )
    )]
    #[OA\Response(ref: '#/components/responses/BadRequestError', response: 400)]
    #[OA\Response(ref: '#/components/responses/NotFoundError', response: 404)]
    #[OA\Response(ref: '#/components/responses/RateLimitError', response: 429)]
    #[OA\Response(ref: '#/components/responses/InternalServerError', response: 500)]
    public function getLast24Hours(
        // DTO is automatically created, populated, and validated by Symfony.
        // If validation fails, a 4xx response is sent automatically by the framework.
        #[MapQueryString]
        GetLast24HoursRatesRequest $dto
    ): JsonResponse {
        $query = new GetLast24HoursRatesQuery($dto->pair);

        // The handle() method dispatches the query and returns the result.
        // Any exception will bubble up to the global ApiExceptionListener.
        $rates = $this->handle($query);

        return $this->json([
            'data' => \array_map(fn ($rate) => CurrencyRateResponseDTO::fromEntity($rate)->toArray(), $rates),
            'meta' => [
                'pair' => $dto->pair,
                'period' => '24h',
                'count' => \count($rates),
                'start_time' => $rates[0]?->getTimestamp()->format('c'),
                'end_time' => \end($rates)?->getTimestamp()->format('c'),
            ],
        ]);
    }

    #[Route('/day', methods: ['GET'])]
    #[OA\Get(
        path: '/api/rates/day',
        summary: 'Get currency rates for a specific day',
        description: 'Retrieves cryptocurrency exchange rates for a specified currency pair on a specific date. Returns all data points collected throughout the requested day (every 5 minutes), providing comprehensive daily trading data.',
        tags: ['Currency Rates']
    )]
    #[OA\Parameter(
        name: 'pair',
        description: 'Currency pair in format BASE/QUOTE. Only EUR-based pairs are supported.',
        in: 'query',
        required: true,
        schema: new OA\Schema(
            type: 'string',
            enum: ['EUR/BTC', 'EUR/ETH', 'EUR/LTC'],
            example: 'EUR/BTC'
        )
    )]
    #[OA\Parameter(
        name: 'date',
        description: 'Date in YYYY-MM-DD format. Future dates are not allowed.',
        in: 'query',
        required: true,
        schema: new OA\Schema(
            type: 'string',
            format: 'date',
            pattern: '^\d{4}-\d{2}-\d{2}$',
            example: '2025-09-07'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful response with currency rates data for the specified day',
        content: new OA\JsonContent(
            ref: '#/components/schemas/CurrencyRatesResponse'
        )
    )]
    #[OA\Response(ref: '#/components/responses/BadRequestError', response: 400)]
    #[OA\Response(ref: '#/components/responses/NotFoundError', response: 404)]
    #[OA\Response(ref: '#/components/responses/RateLimitError', response: 429)]
    #[OA\Response(ref: '#/components/responses/InternalServerError', response: 500)]
    public function getSpecificDay(
        #[MapQueryString]
        GetDailyRatesRequest $dto
    ): JsonResponse {
        $query = new GetDailyRatesQuery($dto->pair, $dto->date);
        $rates = $this->handle($query);

        return $this->json([
            'data' => \array_map(fn ($rate) => CurrencyRateResponseDTO::fromEntity($rate)->toArray(), $rates),
            'meta' => [
                'pair' => $dto->pair,
                'period' => 'day',
                'count' => \count($rates),
                'start_time' => $rates[0]?->getTimestamp()->format('c'),
                'end_time' => \end($rates)?->getTimestamp()->format('c'),
            ],
        ]);
    }
}
