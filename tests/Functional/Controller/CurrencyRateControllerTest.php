<?php

declare(strict_types=1);

namespace Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;


class CurrencyRateControllerTest extends WebTestCase
{
    public function testLast24HoursReturnsValidDataStructure(): void
    {
        $response = $this->makeApiRequest('GET', '/api/rates/last-24h?pair=EUR/BTC');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        
        $data = $this->getJsonResponseData($response);
        $this->assertApiResponseStructure($data, [
            'data' => 'array',
            'meta' => 'array'
        ]);
        
        $this->assertSame('EUR/BTC', $data['meta']['pair']);
        $this->assertSame('24h', $data['meta']['period']);
        $this->assertGreaterThan(0, $data['meta']['count']);
    }
    
    public function testInvalidCurrencyPairReturns400(): void
    {
        $response = $this->makeApiRequest('GET', '/api/rates/last-24h?pair=USD/BTC');
        
        $this->assertResponseStatusCodeSame(400);
    }
    
    public function testMissingParameterReturns400(): void
    {
    
        $response = $this->makeApiRequest('GET', '/api/rates/last-24h');
        
        $this->assertResponseStatusCodeSame(400);
    }
    
    public function testDailyEndpointWithValidDate(): void
    {
        $date = (new \DateTime('-1 day'))->format('Y-m-d');
        
        $response = $this->makeApiRequest('GET', "/api/rates/day?pair=EUR/BTC&date={$date}");
        
        $this->assertResponseIsSuccessful();
        
        $data = $this->getJsonResponseData($response);
        $this->assertSame('EUR/BTC', $data['meta']['pair']);
        $this->assertSame('day', $data['meta']['period']);
    }
    
    public function testFutureDateReturns400(): void
    {
        $futureDate = (new \DateTime('+1 day'))->format('Y-m-d');
        $response = $this->makeApiRequest('GET', "/api/rates/day?pair=EUR/BTC&date={$futureDate}");
        
        $this->assertResponseStatusCodeSame(400);
    }
    
    private function makeApiRequest(string $method, string $uri): Response
    {
        $client = self::createClient();
        $client->request($method, $uri);
        
        return $client->getResponse();
    }
    
    /**
     * @return array<string, mixed>
     */
    private function getJsonResponseData(Response $response): array
    {
        $content = $response->getContent();
        $this->assertNotFalse($content, 'Response content should not be false');
        
        $data = json_decode($content, true);
        $this->assertIsArray($data, 'Response should be valid JSON');
        
        return $data;
    }
    
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $expectedStructure
     */
    private function assertApiResponseStructure(array $data, array $expectedStructure): void
    {
        foreach ($expectedStructure as $key => $type) {
            $this->assertArrayHasKey($key, $data, "Response should have key: {$key}");
            
            match ($type) {
                'array' => $this->assertIsArray($data[$key]),
                'string' => $this->assertIsString($data[$key]),
                'int' => $this->assertIsInt($data[$key]),
                'float' => $this->assertIsFloat($data[$key]),
                default => throw new \InvalidArgumentException("Unknown type: {$type}")
            };
        }
    }
}
