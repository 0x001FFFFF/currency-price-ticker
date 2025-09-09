<?php

declare(strict_types=1);

namespace App\Infrastructure\Scheduler;

use App\Application\Command\UpdateCurrencyRatesCommand;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('currency_updates')]
final class CurrencyRateScheduleProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
        ->add(RecurringMessage::every('5 minutes', new UpdateCurrencyRatesCommand()));
    }
}
