<?php

declare(strict_types=1);

use Bcgov\NaadConnector\Exception\AlertFetchFailureException;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Monolog\Logger;

use PHPUnit\Framework\Attributes\{
    CoversClass,
    UsesClass,
    DataProvider
};

use Bcgov\NaadConnector\NaadRepositoryClient;

/**
 * NaadRepositoryTest Class
 * This will test the class constructor, and its methods
 *
 * @category Client
 * @package  NaadConnector
 * @author   Richard O'Brien <Richard.OBrien@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 */
#[CoversClass(NaadRepositoryClient::class)]
#[UsesClass('Bcgov\NaadConnector\Exception\AlertFetchFailureException')]
final class NaadRepositoryClientTest extends TestCase
{
    /**
     * Test NaadRepositoryClient constructor with various URLs
     *
     * @param string $url         The base URL to initialize the class.
     * @param bool   $shouldThrow Whether to throw an exception.
     *
     * @return void
     */
    #[DataProvider('urlProvider')]
    public function testConstructorWithVariousUrls(
        string $url,
        bool $shouldThrow
    ): void {
        if ($shouldThrow) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Base URL cannot be empty');
        }

        $client = new Client(); // Assuming Client is a valid class
        $logger = $this->createStub(Logger::class);
        $naadRepositoryClient = new NaadRepositoryClient($client, $url, $logger);

        if (!$shouldThrow) {
            // Assert that the instance is created successfully
            $this->assertInstanceOf(
                NaadRepositoryClient::class,
                $naadRepositoryClient
            );
        }
    }

    /**
     * Tests  fetchAlert method with valid response and error.
     *
     * @param array  $mockResponse     The mocked response from the guzzle client
     * @param array  $alert            The test alert data used to construct an URL.
     * @param string $expectedBody     The request response body that
     *                                 should be returned in the fetched Alert.
     * @param bool   $expectsException Whether an exception is expected.
     *
     * @return void
     */
    #[DataProvider('fetchAlertProvider')]
    public function testFetchAlert(
        $mockResponse,
        array $alert,
        string $expectedBody,
        bool $expectsException
    ): void {
        $mockClient = $this->createMock(Client::class);
        if ($expectsException) {
            $mockClient->method('get')->willThrowException(
                new RequestException(
                    "Error",
                    $this->createMock(RequestInterface::class)
                )
            );
        } else {
            $mockClient->method('get')->willReturn($mockResponse);
        }

        $logger = $this->createStub(Logger::class);
        $client = new NaadRepositoryClient($mockClient, 'naad.url', $logger);
        $id = $alert['id'];
        $sent = $alert['sent'];

        if ($expectsException) {
            $this->expectException(AlertFetchFailureException::class);
            $this->expectExceptionMessage($expectedBody);
            $client->fetchAlert($id, $sent);
        } else {
            $response = $client->fetchAlert($id, $sent);
            $this->assertEquals($expectedBody, $response);
        }

    }


    // DATA PROVIDERS

    /**
     * Provides fetchAlert() Data.
     *
     * @return array
     */
    public static function fetchAlertProvider(): array
    {
        return [
            'successful fetch' => [
                new Response(200, [], 'Alert Body'),
                ['sent' => '2024-06-17T12:00:00Z', 'id' => '123'],
                'Alert Body',
                false,
            ],
            'request error' => [
                'Request error scenario',
                ['sent' => '2024-06-17T12:00:00Z', 'id' => '123'],
                'Failed to fetch alert: Error',
                true,
            ],
        ];
    }

    /**
     * Data provider for testConstructorWithVariousUrls
     *
     * @return array
     */
    public static function urlProvider(): array
    {
        return [
            'valid URL' => ['https://api.example.com', false],
            'invalid URL' => ['', true],
        ];
    }
}
