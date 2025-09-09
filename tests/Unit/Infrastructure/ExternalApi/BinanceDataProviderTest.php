<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\ExternalApi;

use App\Domain\CurrencyRate\ValueObject\CurrencyPair;
use App\Exception\InvalidResponseException;
use App\Infrastructure\ExternalApi\BinanceDataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class BinanceDataProviderTest extends TestCase
{
    private BinanceDataProvider $dataProvider;

    protected function setUp(): void
    {
        $this->dataProvider = new BinanceDataProvider(new NullLogger());
    }

    public function testTransformToCurrencyRateSuccess(): void
    {
        // Arrange
        $rawData = [
            'symbol' => 'BTCEUR',
            'price' => '45678.90000000',
        ];
        $expectedPair = new CurrencyPair('EUR', 'BTC');

        // Act
        $currencyRate = $this->dataProvider->transformToCurrencyRate($rawData, $expectedPair);

        // Assert
        $this->assertEquals('EUR/BTC', $currencyRate->getPair());
        $this->assertEquals(45678.90, $currencyRate->getRateAsFloat());
        $this->assertEquals('binance', $currencyRate->getSource());
        $this->assertInstanceOf(\DateTimeImmutable::class, $currencyRate->getTimestamp());
    }

    public function testThrowsExceptionForMissingFields(): void
    {
        // Arrange
        $rawData = ['symbol' => 'BTCEUR']; // Missing price
        $expectedPair = new CurrencyPair('EUR', 'BTC');

        // Act & Assert
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Missing required fields in Binance API response');

        $this->dataProvider->transformToCurrencyRate($rawData, $expectedPair);
    }

    public function testThrowsExceptionForSymbolMismatch(): void
    {
        // Arrange
        $rawData = [
            'symbol' => 'ETHEUR', // Wrong symbol
            'price' => '45678.90000000',
        ];
        $expectedPair = new CurrencyPair('EUR', 'BTC'); // Expects BTCEUR

        // Act & Assert
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Symbol mismatch: expected BTCEUR, got ETHEUR');

        $this->dataProvider->transformToCurrencyRate($rawData, $expectedPair);
    }

    public function testThrowsExceptionForInvalidPrice(): void
    {
        // Arrange
        $rawData = [
            'symbol' => 'BTCEUR',
            'price' => '0', // Invalid price
        ];
        $expectedPair = new CurrencyPair('EUR', 'BTC');

        // Act & Assert
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Invalid price value: 0');

        $this->dataProvider->transformToCurrencyRate($rawData, $expectedPair);
    }

    public function testThrowsExceptionForNegativePrice(): void
    {
        // Arrange
        $rawData = [
            'symbol' => 'BTCEUR',
            'price' => '-100', // Negative price
        ];
        $expectedPair = new CurrencyPair('EUR', 'BTC');

        // Act & Assert
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Invalid price value: -100');

        $this->dataProvider->transformToCurrencyRate($rawData, $expectedPair);
    }
}
