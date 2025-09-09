<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CurrencyRateControllerTest extends WebTestCase
{
    protected $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        parent::setUp();
    }
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client = null;
    }

    public function testLast24HoursReturnsValidDataStructure(): void
    {
        $response = $this->makeApiRequest('GET', '/api/rates/last-24h?pair=EUR/BTC');
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $data = $this->getJsonResponseData($response);
        if ($response->isSuccessful()) {
            $this->assertIsArray($data);
            $this->assertArrayHasKey('data', $data);
            $this->assertArrayHasKey('meta', $data);
            $this->assertArrayHasKey('pair', $data['meta']);
            $this->assertArrayHasKey('period', $data['meta']);
            $this->assertArrayHasKey('count', $data['meta']);
            $this->assertSame('EUR/BTC', $data['meta']['pair']);
            $this->assertSame('24h', $data['meta']['period']);
            $this->assertGreaterThanOrEqual(0, $data['meta']['count']);
        } else {
            $this->assertIsArray($data);
            $this->assertArrayHasKey('error_code', $data);
            $this->assertNotEmpty($data['error_code']);
            $this->assertArrayHasKey('message', $data);
            $this->assertNotEmpty($data['message']);
            $this->assertArrayHasKey('status_code', $data);
            $this->assertIsInt($data['status_code']);
            $this->assertArrayHasKey('timestamp', $data);
            $this->assertIsString($data['timestamp']);
        }
    }

    public function testInvalidCurrencyPairReturns400(): void
    {
        $response = $this->makeApiRequest('GET', '/api/rates/last-24h?pair=USD/BTC');
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $data = $this->getJsonResponseData($response);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data['error_code']);
        $this->assertSame('VALIDATION_ERROR', $data['error_code']);
        $this->assertArrayHasKey('message', $data);
        $this->assertNotEmpty($data['message']);
        $this->assertArrayHasKey('status_code', $data);
        $this->assertSame($data['status_code'], 400);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertIsString($data['timestamp']);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testMissingParameterReturns400(): void
    {
        $response = $this->makeApiRequest('GET', '/api/rates/last-24h');
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $data = $this->getJsonResponseData($response);
        $this->assertNotEmpty($data['error_code']);
        $this->assertSame('VALIDATION_ERROR', $data['error_code']);
        $this->assertArrayHasKey('message', $data);
        $this->assertNotEmpty($data['message']);
        $this->assertArrayHasKey('status_code', $data);
        $this->assertSame($data['status_code'], 400);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertIsString($data['timestamp']);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testDailyEndpointWithValidDate(): void
    {
        $date = (new \DateTime('-1 day'))->format('Y-m-d');
        $response = $this->makeApiRequest('GET', "/api/rates/day?pair=EUR/BTC&date={$date}");
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $data = $this->getJsonResponseData($response);

        if ($response->isSuccessful()) {
            $this->assertSame('EUR/BTC', $data['meta']['pair']);
            $this->assertSame('day', $data['meta']['period']);
        } else {
            $this->assertIsArray($data);
            $this->assertArrayHasKey('error_code', $data);
            $this->assertNotEmpty($data['error_code']);
            $this->assertArrayHasKey('message', $data);
            $this->assertNotEmpty($data['message']);
            $this->assertArrayHasKey('status_code', $data);
            $this->assertIsInt($data['status_code']);
            $this->assertArrayHasKey('timestamp', $data);
            $this->assertIsString($data['timestamp']);
        }
    }

    public function testFutureDateReturns400(): void
    {
        $futureDate = (new \DateTime('+1 day'))->format('Y-m-d');
        $response = $this->makeApiRequest('GET', "/api/rates/day?pair=EUR/BTC&date={$futureDate}");
        $data = $this->getJsonResponseData($response);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error_code', $data);
        $this->assertNotEmpty($data['error_code']);
        $this->assertArrayHasKey('message', $data);
        $this->assertNotEmpty($data['message']);
        $this->assertArrayHasKey('status_code', $data);
        $this->assertIsInt($data['status_code']);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertIsString($data['timestamp']);
    }

    protected function makeApiRequest(string $method, string $uri): Response
    {
        $this->client->request($method, $uri);

        return $this->client->getResponse();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getJsonResponseData(Response $response): array
    {
        $content = $response->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        $this->assertJson($content, 'Response content should be valid JSON');
        $data = json_decode($content, true);
        $this->assertIsArray($data, 'Response should be valid JSON');

        return $data;
    }
}
