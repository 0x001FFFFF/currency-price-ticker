<?php

declare(strict_types=1);

namespace App\Application\Command;

final readonly class UpdateCurrencyRatesCommand
{
    /**
     * @param array<string>|null $specificPairs
     */
    public function __construct(
        private bool $force = false,
        private ?array $specificPairs = null
    ) {
    }

    public function isForced(): bool
    {
        return $this->force;
    }

    /**
     * @return array<string>|null
     */
    public function getSpecificPairs(): ?array
    {
        return $this->specificPairs;
    }

    public function shouldUpdateAllPairs(): bool
    {
        return $this->specificPairs === null;
    }
}
