<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

class ExceptionHandlingTest extends CurrencyRateControllerTest
{
    private function assertNoSensitiveInformation(array $data): void
    {
        $response = json_encode($data);

        $sensitiveTerms = [
            // File paths
            '/var/', '/home/', '/usr/', '/etc/', '/opt/', '/tmp/',
            'C:\\', 'D:\\', '.php', '.env', '.log',

            // Database information
            'mysql', 'MariaDB', 'PostgreSQL', 'SQLite', 'password', 'username',
            'database', 'connection', 'host', 'port', '3306', '5432',

            // Stack trace information
            'stack', 'trace', 'line', 'file', 'function', 'class',

            // Configuration information
            'config', 'secret', 'key', 'token', 'api_key',

            // System information
            'version', 'server', 'php', 'apache', 'nginx',
        ];

        foreach ($sensitiveTerms as $term) {
            $this->assertStringNotContainsStringIgnoringCase($term, $response,
                "Response should not contain sensitive information: '$term'");
        }
    }
    private function assertValidErrorStructure(array $data): void
    {
        // Verify required error fields
        $this->assertArrayHasKey('error_code', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('status_code', $data);
        $this->assertArrayHasKey('timestamp', $data);

        // Verify field types
        $this->assertIsString($data['error_code']);
        $this->assertIsString($data['message']);
        $this->assertIsInt($data['status_code']);
        $this->assertIsString($data['timestamp']);

        // Verify non-empty values
        $this->assertNotEmpty($data['error_code']);
        $this->assertNotEmpty($data['message']);
        $this->assertGreaterThan(0, $data['status_code']);
        $this->assertNotEmpty($data['timestamp']);

        // Verify error code format (uppercase with underscores)
        $this->assertMatchesRegularExpression('/^[A-Z_]+$/', $data['error_code']);

        // Verify status code is valid HTTP status
        $this->assertGreaterThanOrEqual(400, $data['status_code']);
        $this->assertLessThanOrEqual(599, $data['status_code']);
    }

    public function testHttp400ValidationErrors(): void
    {
        $testCases = [
            // Missing required parameters
            [
                'uri' => '/api/rates/last-24h',
                'description' => 'Missing pair parameter',
            ],

            // Invalid parameter values
            [
                'uri' => '/api/rates/last-24h?pair=INVALID/PAIR',
                'description' => 'Invalid currency pair',
            ],
            [
                'uri' => '/api/rates/day?pair=EUR/BTC&date=invalid-date',
                'description' => 'Invalid date format',
            ],
            // Malformed parameters
            [
                'uri' => '/api/rates/last-24h?pair=',
                'description' => 'Empty pair parameter',
            ],
            [
                'uri' => '/api/rates/day?pair=EUR/BTC&date=',
                'description' => 'Empty date parameter',
            ],
        ];

        foreach ($testCases as $testCase) {
            $response = $this->makeApiRequest('GET', $testCase['uri']);

            $this->assertSame(400, $response->getStatusCode(),
                "Should return 400 for: " . $testCase['description']);
            $data = $this->getJsonResponseData($response);
            // Verify no sensitive information is leaked
            $this->assertNoSensitiveInformation($data);
            $this->assertValidErrorStructure($data);
        }
    }


    public function testHttp405MethodNotAllowed(): void
    {
        $endpoints = [
            '/api/rates/last-24h?pair=EUR/BTC',
            '/api/rates/day?pair=EUR/BTC&date=2025-01-15',
        ];

        $disallowedMethods = ['POST', 'PUT', 'DELETE', 'PATCH'];

        foreach ($endpoints as $endpoint) {
            foreach ($disallowedMethods as $method) {
                $response = $this->makeApiRequest($method, $endpoint);

                $this->assertSame(405, $response->getStatusCode(),
                    "Should return 405 for $method on $endpoint");

                self::assertResponseHeaderSame('Content-Type', 'application/json');
                $data = $this->getJsonResponseData($response);
                $this->assertArrayHasKey('error_code', $data);
                $this->assertArrayHasKey('message', $data);
                $this->assertArrayHasKey('status_code', $data);
                $this->assertSame(405, $data['status_code']);
                $this->assertNoSensitiveInformation($data);
            }
        }
    }

    public function testHttp429RateLimitExceeded(): void
    {
        $endpoint = '/api/rates/last-24h?pair=EUR/BTC';

        // Make requests until rate limited
        $rateLimitResponse = null;
        for ($i = 0; $i < 110; $i++) {
            $response = $this->makeApiRequest('GET', $endpoint);

            if ($response->getStatusCode() === 429) {
                $rateLimitResponse = $response;
                break;
            }
        }

        if ($rateLimitResponse) {
            // Verify rate limit specific headers
            $this->assertTrue($rateLimitResponse->headers->has('Retry-After'));
            $retryAfter = $rateLimitResponse->headers->get('Retry-After');
            $this->assertIsNumeric($retryAfter);
            $this->assertGreaterThan(0, (int) $retryAfter);

            $data = $this->getJsonResponseData($rateLimitResponse);
            $this->assertNoSensitiveInformation($data);
            $this->assertValidErrorStructure($data);

            // Verify message is user-friendly
            $this->assertStringContainsString('rate limit', strtolower($data['message']));
        } else {
            $this->markTestSkipped('Rate limiting not enforced or limit too high for test');
        }
    }

    public function testRateLimitSecurityHeaders(): void
    {
        $endpoint = '/api/rates/last-24h?pair=EUR/BTC';

        // Make requests until rate limited
        $response = null;
        for ($i = 0; $i < 110; $i++) {
            $response = $this->makeApiRequest('GET', $endpoint);

            if ($response->getStatusCode() === 429) {
                break;
            }
        }

        $this->assertNotNull($response);
        $this->assertSame(429, $response->getStatusCode());

        // Verify security headers
        $this->assertTrue($response->headers->has('Retry-After'));
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        // Ensure no sensitive information is leaked
        $data = $this->getJsonResponseData($response);
        $this->assertArrayNotHasKey('debug', $data);
        $this->assertArrayNotHasKey('trace', $data);
        $this->assertArrayNotHasKey('file', $data);
        $this->assertArrayNotHasKey('line', $data);
    }
}
