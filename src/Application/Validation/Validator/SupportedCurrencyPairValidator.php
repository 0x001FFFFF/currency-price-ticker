<?php

declare(strict_types=1);

namespace App\Application\Validation\Validator;

use App\Application\Validation\Constraint\SupportedCurrencyPair;
use App\Exception\InvalidCurrencyPairException;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class SupportedCurrencyPairValidator extends ConstraintValidator
{
    public function __construct(
        private readonly array $supportedPairs
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!$constraint instanceof SupportedCurrencyPair) {
            throw new InvalidArgumentException(sprintf(
                'Expected instance of %s, got %s',
                SupportedCurrencyPair::class,
                $constraint::class
            ));
        }

        if (!in_array($value, $this->supportedPairs, true)) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ choices }}', implode(', ', $this->supportedPairs))
                ->addViolation();
        }
    }
}
