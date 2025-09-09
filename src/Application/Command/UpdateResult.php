<?php

declare(strict_types=1);

namespace App\Application\Command;

final class UpdateResult
{
    /** @var array<string> */
    private array $successful = [];
    /** @var array<string> */
    private array $updated = [];
    /** @var array<string, string> */
    private array $errors = [];

    public function addSuccess(string $pair): void
    {
        $this->successful[] = $pair;
    }

    public function addUpdated(string $pair): void
    {
        $this->updated[] = $pair;
    }

    public function addError(string $pair, string $error): void
    {
        $this->errors[$pair] = $error;
    }

    public function getSuccessCount(): int
    {
        return count($this->successful);
    }

    public function getUpdatedCount(): int
    {
        return count($this->updated);
    }

    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    public function isCompleteSuccess(): bool
    {
        return empty($this->errors) && ! empty($this->successful);
    }
}
