<?php

declare(strict_types=1);

namespace App\Exception;

class ExternalApiException extends InfrastructureException
{
    /**
     * @param array<string, mixed>|null $context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        private readonly ?array $context = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context ?? [];
    }
}
