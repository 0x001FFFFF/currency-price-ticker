<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\Command\UpdateCurrencyRatesCommand;
use App\Application\Command\UpdateCurrencyRatesHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-currency-rates',
    description: 'Updates currency rates from external API (Binance)'
)]
final class UpdateCurrencyRatesConsoleCommand extends Command
{
    public function __construct(
        private readonly UpdateCurrencyRatesHandler $handler
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force update even if rates already exist for current time period'
            )
            ->addOption(
                'pairs',
                'p',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Specific currency pairs to update (e.g., EUR/BTC EUR/ETH)',
                []
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Currency Rates Update');
        $io->text('Starting currency rates update from Binance API...');

        $force = $input->getOption('force');
        $specificPairs = $input->getOption('pairs');

        try {
            $command = new UpdateCurrencyRatesCommand(
                $force,
                empty($specificPairs) ? null : $specificPairs
            );

            $result = $this->handler->__invoke($command);

            // Display results
            $io->success('Update completed successfully!');

            $io->definitionList(
                ['Successful updates' => $result->getSuccessCount()],
                ['Updated rates' => $result->getUpdatedCount()],
                ['Errors' => $result->getErrorCount()]
            );

            if ($result->hasErrors()) {
                $io->error('Some updates failed:');
                foreach ($result->getErrors() as $pair => $error) {
                    $io->text("  • {$pair}: {$error}");
                }

                return Command::FAILURE;
            }

            return Command::SUCCESS;

        } catch (\Throwable $exception) {
            $io->error('Update failed completely: ' . $exception->getMessage());

            return Command::FAILURE;
        }
    }
}
