<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\CurrencyRate\ValueObject;

use App\Domain\CurrencyRate\ValueObject\CurrencyPair;
use App\Exception\InvalidCurrencyPairException;
use PHPUnit\Framework\TestCase;

final class CurrencyPairTest extends TestCase
{
    public function testCanCreateValidCurrencyPair(): void
    {
        $pair = new CurrencyPair('EUR', 'BTC');

        $this->assertEquals('EUR/BTC', $pair->toString());
        $this->assertEquals('BTCEUR', $pair->toBinanceSymbol());
        $this->assertEquals('EUR', $pair->getBaseCurrency());
        $this->assertEquals('BTC', $pair->getQuoteCurrency());
    }

    public function testThrowsExceptionForUnsupportedPair(): void
    {
        $this->expectException(InvalidCurrencyPairException::class);
        $this->expectExceptionMessage('Currency pair "USD/BTC" is not supported');

        new CurrencyPair('USD', 'BTC');
    }

    public function testThrowsExceptionForInvalidCurrencyLength(): void
    {
        $this->expectException(InvalidCurrencyPairException::class);
        $this->expectExceptionMessage('Currency codes must be exactly 3 characters long');

        new CurrencyPair('EU', 'BTC');
    }

    public function testEquals(): void
    {
        $pair1 = new CurrencyPair('EUR', 'BTC');
        $pair2 = new CurrencyPair('EUR', 'BTC');
        $pair3 = new CurrencyPair('EUR', 'ETH');

        $this->assertTrue($pair1->equals($pair2));
        $this->assertFalse($pair1->equals($pair3));
    }

    public function testAllSupportedPairsAreValid(): void
    {
        $testCases = [
            ['EUR', 'BTC', 'BTCEUR'],
            ['EUR', 'ETH', 'ETHEUR'],
            ['EUR', 'LTC', 'LTCEUR'],
        ];

        foreach ($testCases as [$base, $quote, $expectedSymbol]) {
            $pair = new CurrencyPair($base, $quote);
            $this->assertEquals($expectedSymbol, $pair->toBinanceSymbol());
        }
    }
}
