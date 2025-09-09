<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\ExternalApi;

use App\Exception\ExternalApiException;
use App\Infrastructure\ExternalApi\BinanceApiClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class BinanceApiClientTest extends TestCase
{
    public function testFetchRateDataSuccess(): void
    {
        // Arrange
        $mockResponse = new MockResponse(json_encode([
            'symbol' => 'BTCEUR',
            'price' => '45678.90000000',
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $client = new BinanceApiClient($httpClient, new NullLogger());

        // Act
        $result = $client->fetchRateData('BTCEUR');

        // Assert
        $this->assertEquals('BTCEUR', $result['symbol']);
        $this->assertEquals('45678.90000000', $result['price']);
    }

    public function testRetryLogicOnTransportException(): void
    {
        // Arrange - first two calls fail, third succeeds
        $responses = [
            new MockResponse('', ['http_code' => 0]), // Timeout
            new MockResponse('', ['http_code' => 0]), // Timeout
            new MockResponse(json_encode([
                'symbol' => 'BTCEUR',
                'price' => '45678.90',
            ])), // Success on 3rd attempt
        ];

        $httpClient = new MockHttpClient($responses);
        $client = new BinanceApiClient($httpClient, new NullLogger());

        // Act
        $result = $client->fetchRateData('BTCEUR');

        // Assert
        $this->assertEquals('BTCEUR', $result['symbol']);
        $this->assertEquals('45678.90', $result['price']);
    }

    public function testThrowsExceptionAfterMaxRetries(): void
    {
        // Arrange - all calls fail
        $responses = [
            new MockResponse('', ['http_code' => 0]),
            new MockResponse('', ['http_code' => 0]),
            new MockResponse('', ['http_code' => 0]),
        ];

        $httpClient = new MockHttpClient($responses);
        $client = new BinanceApiClient($httpClient, new NullLogger());

        // Act & Assert
        $this->expectException(ExternalApiException::class);
        $this->expectExceptionMessage('Request failed after 3 attempts');

        $client->fetchRateData('BTCEUR');
    }

    public function testHealthCheckReturnsTrueOnSuccess(): void
    {
        // Arrange
        $mockResponse = new MockResponse('{}');
        $httpClient = new MockHttpClient($mockResponse);
        $client = new BinanceApiClient($httpClient, new NullLogger());

        // Act & Assert
        $this->assertTrue($client->healthCheck());
    }

    public function testHealthCheckReturnsFalseOnFailure(): void
    {
        // Arrange
        $mockResponse = new MockResponse('', ['http_code' => 500]);
        $httpClient = new MockHttpClient($mockResponse);
        $client = new BinanceApiClient($httpClient, new NullLogger());

        // Act & Assert
        $this->assertFalse($client->healthCheck());
    }
}
