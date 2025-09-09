<?php

declare(strict_types=1);

namespace App\Infrastructure\Scheduler;

use App\Application\Command\UpdateCurrencyRatesCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

// #[AsPeriodicTask(frequency: '5 minutes', jitter: 30)]
final class CurrencyRateUpdateTask
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(): void
    {
        $this->logger->info('Scheduled currency rate update task triggered', [
            'timestamp' => (new \DateTimeImmutable())->format('c'),
        ]);

        try {
            $command = new UpdateCurrencyRatesCommand();
            $this->messageBus->dispatch($command);

            $this->logger->info('Currency rate update command dispatched successfully');

        } catch (\Throwable $exception) {
            $this->logger->error('Failed to dispatch currency rate update command', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            // Don't re-throw to prevent scheduler from stopping
        }
    }
}
