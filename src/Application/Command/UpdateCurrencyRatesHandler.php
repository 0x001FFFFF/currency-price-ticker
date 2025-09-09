<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Application\Service\CurrencyRateService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateCurrencyRatesHandler
{
    public function __construct(
        private readonly CurrencyRateService $currencyRateService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(UpdateCurrencyRatesCommand $command): UpdateResult
    {
        $this->logger->info('Processing UpdateCurrencyRatesCommand', [
            'forced' => $command->isForced(),
            'specific_pairs' => $command->getSpecificPairs(),
            'update_all' => $command->shouldUpdateAllPairs(),
        ]);

        try {
            if ($command->shouldUpdateAllPairs()) {
                return $this->currencyRateService->updateAllRates($command->isForced());
            } else {
                $specificPairs = $command->getSpecificPairs() ?? [];

                return $this->currencyRateService->updateSpecificRates(
                    $specificPairs,
                    $command->isForced()
                );
            }
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to process UpdateCurrencyRatesCommand', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }
}
