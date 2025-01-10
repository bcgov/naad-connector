<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\{
    Test,
    CoversClass,
    UsesClass
};
use PHPUnit\Framework\TestCase;
use Bcgov\NaadConnector\{
    CustomLogger,
    Database,
    DestinationClient
};
use Bcgov\NaadConnector\Entity\Alert;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

/**
 * DestinationClientTest Class for testing DestinationClient.
 *
 * @category Client
 * @package  NaadConnector
 * @author   Kyle Shapka <Kyle.Shapka@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 */
#[CoversClass('Bcgov\NaadConnector\DestinationClient')]
#[UsesClass('Bcgov\NaadConnector\Entity\Alert')]
final class DestinationClientTest extends TestCase
{
    private $mockDatabase;
    private $mockLogger;
    private $mockHttpClient;

    /**
     * Set up the test environment before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->mockDatabase = $this->createMock(Database::class);
        $this->mockLogger = $this->createMock(CustomLogger::class);
        $this->mockHttpClient = $this->createMock(Client::class);
    }

    /**
     * Tests the constructor of the DestinationClient.
     *
     * @return void
     */
    #[Test]
    public function testConstructor()
    {
        $destinationClient = new DestinationClient(
            'http://example.com',
            'username',
            'password',
            $this->mockLogger,
            $this->mockDatabase,
            $this->mockHttpClient
        );

        $this->assertInstanceOf(DestinationClient::class, $destinationClient);
    }

    /**
     * Tests sendAlerts method when all alerts are successfully sent.
     *
     * @return void
     */
    #[Test]
    public function testSendAlertsSuccess()
    {
        $alert = $this->createMock(Alert::class);
        $alert->method('getBody')->willReturn('<alert>data</alert>');
        $alert->expects($this->once())->method('setSuccess')->with(true);
        $alert->expects($this->once())->method('setSendAttempted');
        $alert->expects($this->never())->method('incrementFailures');

        $this->mockDatabase->method('getUnsentAlerts')->willReturn([$alert]);

        $this->mockHttpClient->method('post')
            ->willReturn(new Response(200, [], 'OK'));
        $this->mockLogger->expects($this->never())->method('critical');

        $destinationClient = new DestinationClient(
            'http://example.com',
            'user',
            'pass',
            $this->mockLogger,
            $this->mockDatabase,
            $this->mockHttpClient
        );

        $this->assertTrue($destinationClient->sendAlerts());
    }

        /**
     * Tests sendAlerts method when all alerts are successfully sent.
     *
     * @return void
     */
    #[Test]
    public function testSendAlertsFailure()
    {
        $alert = $this->createMock(Alert::class);
        $alert->method('getBody')->willReturn('<alert>data</alert>');
        $alert->expects($this->once())->method('setSuccess')->with(false);
        $alert->expects($this->once())->method('setSendAttempted');
        $alert->expects($this->once())->method('incrementFailures');

        $this->mockDatabase->method('getUnsentAlerts')->willReturn([$alert]);

        $exception = new ConnectException(
            'Connection error',
            new \GuzzleHttp\Psr7\Request('POST', 'test')
        );
        $this->mockHttpClient->method('post')
            ->willThrowException($exception);
        $this->mockLogger->expects($this->once())->method('error');

        $destinationClient = new DestinationClient(
            'http://example.com',
            'user',
            'pass',
            $this->mockLogger,
            $this->mockDatabase,
            $this->mockHttpClient
        );

        $this->assertFalse($destinationClient->sendAlerts());
    }

    /**
     * Tests sendRequest method handles ConnectException properly.
     *
     * @return void
     */
    #[Test]
    public function testSendRequestHandlesConnectException()
    {
        $exception = new ConnectException(
            'Connection error',
            new \GuzzleHttp\Psr7\Request('POST', 'test')
        );
        $this->mockHttpClient->method('post')->willThrowException($exception);

        $client = new DestinationClient(
            'http://example.com',
            'user',
            'password',
            $this->mockLogger,
            $this->mockDatabase,
            $this->mockHttpClient
        );

        $result = $client->sendRequest('<xml></xml>');
        $this->assertSame(
            ['status_code' => 0, 'body' => 'Connection error: Connection error'],
            $result
        );
    }

    /**
     * Tests sendRequest method handles other HTTP errors with response.
     *
     * @return void
     */
    #[Test]
    public function testSendRequestHandlesHttpErrorWithResponse()
    {
        $mockClient = $this->createMock(Client::class);
        $exception = new RequestException(
            'Error',
            new \GuzzleHttp\Psr7\Request('POST', 'test'),
            new Response(500, [], 'Internal Server Error')
        );
        $mockClient->method('post')->willThrowException($exception);

        $client = new DestinationClient(
            'http://example.com',
            'user',
            'password',
            $this->createMock(CustomLogger::class),
            $this->createMock(Database::class),
            $mockClient
        );

        $result = $client->sendRequest('<xml></xml>');

        $this->assertSame(
            [
                'status_code' => 500,
                'body'        => 'Internal Server Error',
            ],
            $result
        );
    }

    /**
     * Tests sendRequest method throws an exception when no response is available.
     *
     * @return void
     */
    #[Test]
    public function testSendRequestThrowsExceptionWithoutResponse()
    {
        $exception = new RequestException(
            'Error',
            new \GuzzleHttp\Psr7\Request('POST', 'test')
        );
        $this->mockHttpClient->method('post')->willThrowException($exception);

        $this->expectException(RequestException::class);
        
        $client = new DestinationClient(
            'http://example.com',
            'user',
            'password',
            $this->mockLogger,
            $this->mockDatabase,
            $this->mockHttpClient
        );

        $client->sendRequest('<xml></xml>');
    }
}
