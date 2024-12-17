<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

use PHPUnit\Framework\Attributes\{
    Test,
    CoversClass,
    DataProvider
};

use Bcgov\NaadConnector\{
    NaadRepositoryClient,
    NaadVars
};

/**
 * NaadRepositoryTest Class
 * This will test the class constructor, and these methods:
 * - fetchAlert()
 * - getURL()
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
    private NaadVars $naadVarsMock;
    private Client $guzzleClientMock;

    /**
     * Centralized setup for reusable mocks.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->naadVarsMock = $this->createMock(NaadVars::class);
        $this->guzzleClientMock = $this->createMock(Client::class);
    }

    /**
     * Tests that the NaadRepositoryClient constructor initializes the
     * baseUrl property correctly.
     *
     * @return void
     */
    #[Test]
    public function testConstructorInitializesBaseUrl(): void
    {
        $this->naadVarsMock->naadRepoUrl = 'https://mock-naad-repo.com';

        $client = new NaadRepositoryClient(null, $this->naadVarsMock);

        $this->assertSame('https://mock-naad-repo.com', $client->baseUrl);
    }

    /**
     * Tests that the NaadRepositoryClient constructURL method
     * returns the expected URL.
     *
     * @param array $reference - The URL sent/id reference data
     *                         used to generate an URL.
     *
     * @return void
     */
    #[Test]
    #[DataProvider('referenceData')]
    public function testConstructURL(array $reference): void
    {
        $this->naadVarsMock->naadRepoUrl = 'test.com';
        $client = new NaadRepositoryClient(null, $this->naadVarsMock);

        $expectedUrl = $client->constructURL($reference);

        $this->assertEquals($expectedUrl, $client->constructURL($reference));
    }

    /**
     * Tests that the NaadRepositoryClient fetchAlert method
     * returns the expected response from the mock Guzzle client.
     *
     * @param array $reference - The URL sent/id reference data
     *                         used to generate an URL.
     *
     * @return void
     */
    #[Test]
    #[DataProvider('referenceData')]
    public function testFetchAlert(array $reference): void
    {
        $this->naadVarsMock->naadRepoUrl = 'mock-repo-url.com';
        $this->guzzleClientMock
            ->method('get')
            ->willReturn(new Response(200, [], 'Mock Alert Response'));

        $client = new NaadRepositoryClient(
            $this->guzzleClientMock,
            $this->naadVarsMock
        );

        $response = $client->fetchAlert($reference);

        $this->assertEquals('Mock Alert Response', $response);
    }

    /**
     * Tests that the NaadRepositoryClient fetchAlert method throws an exception
     * when the Guzzle client throws a RequestException.
     *
     * @param array $reference - The URL sent/id reference data
     *                         used to generate an URL.
     *
     * @return void
     */
    #[Test]
    #[DataProvider('referenceData')]
    public function testFetchAlertThrowsException(array $reference): void
    {
        $this->naadVarsMock->naadRepoUrl = 'mock-repo-url.com';
        $this->guzzleClientMock
            ->method('get')
            ->willThrowException(
                new RequestException(
                    'Error',
                    new \GuzzleHttp\Psr7\Request('GET', 'test')
                )
            );

        $client = new NaadRepositoryClient(
            $this->guzzleClientMock, $this->naadVarsMock
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Request failed: Error');

        $client->fetchAlert($reference);
    }

    /**
     * Tests that the NaadRepositoryClient magic getter returns the expected
     * property value or throws an exception for non-existent properties.
     *
     * @param string $property    - The object property we want to '__get'.
     * @param bool   $shouldThrow - Whether to throw an Exception
     *
     * @return void
     */
    #[Test]
    #[DataProvider('getterData')]
    public function testMagicGetter(string $property, bool $shouldThrow): void
    {
        $this->naadVarsMock->naadRepoUrl = $property;
        $client = new NaadRepositoryClient(
            $this->guzzleClientMock, $this->naadVarsMock
        );

        if ($shouldThrow) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage("Property '$property' does not exist.");

            $client->nonExistentProperty;
        } else {
            $this->assertSame($property, $client->baseUrl);
        }
    }

    /**
     * Returns an array of reference data used for testing the NaadRepositoryClient.
     *
     * The array contains key-value pairs where the key is a descriptive name for the
     * reference data and the value is an array of arrays containing 'sent' and 'id'
     * values used to generate URLs for testing.
     *
     * @return array
     */
    public static function referenceData(): array
    {
        return [
            'Reference #1' =>
            [['sent' => '2024-12-16T10:00:00', 'id' => '1234567890']],
            'Reference #2' =>
            [['sent' => '2023-11-15T08:30:00', 'id' => '0987654321']],
            'Midnight Edge Case' =>
            [['sent' => '2024-01-01T00:00:00', 'id' => '1122334455']],
            'Long ID' =>
            [['sent' => '2025-05-25T12:45:00', 'id' => '12345678901234567890']],
            'Special Characters' =>
            [['sent' => '2022-07-04T14:30:00', 'id' => 'abc-123:456+789']],
        ];
    }

    /**
     * Returns an array of data used for testing the NaadRepositoryClient's
     * magic getter.
     *
     * The array contains key-value pairs where the key is a descriptive name for the
     * test case and the value is an array containing the property name and a boolean
     * indicating whether the property should throw an exception.
     *
     * @return array
     */
    public static function getterData(): array
    {
        return [
            'Valid baseUrl' => ['https://example.com', false],
            'Non-existent property' => ['nonExistentProperty', true],
        ];
    }
}
