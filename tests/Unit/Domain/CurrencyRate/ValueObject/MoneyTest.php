<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\CurrencyRate\ValueObject;

use App\Domain\CurrencyRate\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testCanCreateFromFloat(): void
    {
        $money = Money::fromFloat(123.45, 'EUR');

        $this->assertEquals(123.45, $money->getAmount());
        $this->assertEquals('EUR', $money->getCurrency());
    }

    public function testCanCreateFromString(): void
    {
        $money = Money::fromString('123.45000000', 'BTC');

        $this->assertEquals(123.45, $money->getAmount());
        $this->assertEquals('BTC', $money->getCurrency());
    }

    public function testFormatForStorage(): void
    {
        $money = Money::fromFloat(123.456789123, 'EUR');

        $this->assertEquals('123.45678912', $money->formatForStorage());
    }

    public function testIsGreaterThan(): void
    {
        $money1 = Money::fromFloat(100.0);
        $money2 = Money::fromFloat(50.0);
        $money3 = Money::fromFloat(150.0);

        $this->assertTrue($money1->isGreaterThan($money2));
        $this->assertFalse($money1->isGreaterThan($money3));
    }

    public function testEquals(): void
    {
        $money1 = Money::fromFloat(100.0, 'EUR');
        $money2 = Money::fromFloat(100.0, 'EUR');
        $money3 = Money::fromFloat(100.0, 'USD');
        $money4 = Money::fromFloat(99.99999999, 'EUR'); // Within precision tolerance

        $this->assertTrue($money1->equals($money2));
        $this->assertFalse($money1->equals($money3)); // Different currency
        $this->assertTrue($money1->equals($money4)); // Within precision tolerance
    }

    public function testThrowsExceptionForNegativeAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Money amount cannot be negative');

        Money::fromFloat(-10.0);
    }

    public function testThrowsExceptionForEmptyCurrency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency cannot be empty');

        new Money(100.0, '');
    }
}
