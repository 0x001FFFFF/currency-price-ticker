<?php

declare(strict_types=1);

namespace App\Application\Validation\Constraint;

use App\Application\Validation\Validator\SupportedCurrencyPairValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class SupportedCurrencyPair extends Constraint
{
    public string $message = 'Invalid currency pair. Allowed: {{ choices }}';

    public function validatedBy(): string
    {
        return SupportedCurrencyPairValidator::class;
    }
}
