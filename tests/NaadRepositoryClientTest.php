<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

use PHPUnit\Framework\Attributes\{
    CoversClass,
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
        $naadRepositoryClient = new NaadRepositoryClient($client, $url);

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
     * @param array  $reference        The test data used to construct an URL.
     * @param string $expectedBody     The request response body that
     *                                 should be returned in the fetched Alert.
     * @param bool   $expectsException Whether an exception is expected.
     *
     * @return void
     */
    #[DataProvider('fetchAlertProvider')]
    public function testFetchAlert(
        $mockResponse,
        array $reference,
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

        $client = new NaadRepositoryClient($mockClient, 'naad.url');

        if ($expectsException) {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage($expectedBody);
            $client->fetchAlert($reference);
        } else {
            $response = $client->fetchAlert($reference);
            $this->assertEquals($expectedBody, $response);
        }

    }


    // DATA PROVIDERS

    /**
     * Provides constructURL test data.
     *
     * @return array
     */
    public static function constructUrlProvider(): array
    {
        return [
            'valid reference 1' => [
                ['sent' => '2024-06-17T12:00:00Z', 'id' => '123'],
                'http://naad.url/2024-06-17/2024_06_17T12_00_00ZI123.xml',
                false,
            ],
            'valid reference 2' => [
                ['sent' => '2024-06-18T14:30:00Z', 'id' => '456'],
                'http://naad.url/2024-06-18/2024_06_18T14_30_00ZI456.xml',
                false,
            ],
            'invalid reference' => [
                [], // invalid reference
                "Reference must contain 'sent' and 'id' keys",
                true,
            ],
        ];
    }

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
