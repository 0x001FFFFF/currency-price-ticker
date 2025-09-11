<?php

declare(strict_types=1);

namespace App\Infrastructure\Scheduler;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('currency_rate_update_task')]
final class CurrencyRateScheduleProvider implements ScheduleProviderInterface
{
    private const LOCK_KEY = 'currency_rate_update';
    private const SCHEDULE_INTERVAL = '* * * * *'; // Every minute
    private const LOCK_TTL = 60; // 1 minute

    public function __construct(
        private readonly LockFactory $lockFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getSchedule(): Schedule
    {
        $this->logger->info('Initializing currency rate update schedule', [
            'interval' => self::SCHEDULE_INTERVAL,
            'lock_key' => self::LOCK_KEY,
            'lock_ttl' => self::LOCK_TTL,
        ]);

        try {
            $schedule = (new Schedule())
                ->add(
                    RecurringMessage::cron(
                        self::SCHEDULE_INTERVAL,
                        new RunCommandMessage('app:update-currency-rates')
                    )
                )
                ->lock($this->lockFactory->createLock(self::LOCK_KEY, self::LOCK_TTL));


            $this->logger->info('Currency rate task schedule initialized successfully');

            return $schedule;

        } catch (\Throwable $exception) {
            $this->logger->error('Failed to initialize currency rate schedule', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }
}
