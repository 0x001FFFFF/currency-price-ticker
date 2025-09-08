<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * No data found exception.
 */
final class NoDataFoundException extends BusinessException
{
    protected string $errorCode = 'NO_DATA_FOUND';
    protected int $statusCode = 404;

    public function __construct(string $pair, string $period)
    {
        parent::__construct(
            sprintf(
                'No data found for pair "%s" in period "%s"',
                $pair,
                $period
            ),
            $this->statusCode,
        );
    }
}
