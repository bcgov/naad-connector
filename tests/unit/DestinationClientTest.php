<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\{
    Test,
    CoversClass,
    UsesClass
};
use PHPUnit\Framework\TestCase;
use Monolog\Logger;

use Bcgov\NaadConnector\{
    Database,
    DestinationClient
};
use Bcgov\NaadConnector\Entity\Alert;
use Bcgov\NaadConnector\Exception\AlertFailureThresholdException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
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
#[UsesClass('Bcgov\NaadConnector\Exception\AlertFailureThresholdException')]
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
        $this->mockLogger = $this->createMock(Logger::class);
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
        $client = $this->createDestinationClient();

        $this->assertInstanceOf(DestinationClient::class, $client);
    }

    /**
     * Tests sendAlerts method when all alerts are successfully sent.
     *
     * @return void
     */
    #[Test]
    public function testSendAlertsSuccessPageNotCreated()
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
        $this->mockLogger->expects($this->once())->method('log')->with('debug');

        // Act
        $client = $this->createDestinationClient();

        $this->assertTrue($client->sendAlerts());
    }

    /**
     * Tests sendAlerts method when all alerts are successfully sent and an
     * Event page was created as a result of the alert.
     *
     * @return void
     */
    #[Test]
    public function testSendAlertsSuccessPageCreated()
    {
        $alert = $this->createMock(Alert::class);
        $alert->method('getBody')->willReturn('<alert>data</alert>');
        $alert->expects($this->once())->method('setSuccess')->with(true);
        $alert->expects($this->once())->method('setSendAttempted');
        $alert->expects($this->never())->method('incrementFailures');

        $this->mockDatabase->method('getUnsentAlerts')->willReturn([$alert]);

        $this->mockHttpClient->method('post')
            ->willReturn(new Response(200, [], 'Event created successfully'));
        $this->mockLogger->expects($this->never())->method('critical');
        $this->mockLogger->expects($this->once())->method('log')->with('info');

        $client = $this->createDestinationClient();

        $this->assertTrue($client->sendAlerts());
    }

     /**
     * Tests sendAlerts method when it fails and throws an error.
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

        // Allow the logger to be called more than once if neccesary
        $this->mockLogger->expects($this->exactly(2))->method('error');

        $client = $this->createDestinationClient();

        $this->assertFalse($client->sendAlerts());
    }

    /**
     * Tests sendAlerts method when and alert request fails and its
     * failure threshold is reached.
     *
     * @return void
     */
    #[Test]
    public function testSendAlertsFailureThresholdReached()
    {
        $alert = $this->createMock(Alert::class);
        $alert->method('getFailures')
            ->willReturn(DestinationClient::FAILURE_THRESHOLD);
        $alert->expects($this->once())->method('setSuccess')->with(false);
        $alert->expects($this->once())->method('setSendAttempted');
        $alert->expects($this->once())->method('incrementFailures');

        $this->mockDatabase->method('getUnsentAlerts')->willReturn([$alert]);
        $client = $this->createDestinationClient();

        // List of fatal response codes to simulate.
        $fatalStatusCodes = [401, 403, 404, 500];

        foreach ($fatalStatusCodes as $statusCode) {
            // Create a mock ResponseInterface that returns the desired status code.
            $response = $this->createMock(ResponseInterface::class);
            $response->method('getStatusCode')->willReturn($statusCode);

            // Simulate the sendRequest method.
            $this->mockHttpClient->method('post')
                ->willReturn($response);

            // Allow the logger to be called more than once if necessary.
            $this->mockLogger->expects($this->exactly(2))->method('error');

            $this->expectExceptionMessageMatches('/Failure threshold/');
            $this->expectException(AlertFailureThresholdException::class);
            $client->sendAlerts();
        }
    }

    /**
     * Tests sendAlerts method when and alert request fails
     * in a non-fatal way.
     *
     * @return void
     */
    #[Test]
    public function testSendAlertsFailureNonFatal()
    {
        $alert = $this->createMock(Alert::class);
        $alert->expects($this->once())->method('setSuccess')->with(false);
        $alert->expects($this->once())->method('setSendAttempted');
        $alert->expects($this->once())->method('incrementFailures');

        $this->mockDatabase->method('getUnsentAlerts')->willReturn([$alert]);
        $client = $this->createDestinationClient();

        // Create a mock ResponseInterface that returns the desired 5xx status code.
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(413);

        // Simulate the sendRequest method to return the mocked ResponseInterface.
        $this->mockHttpClient->method('post')
            ->willReturn($response);

        // Allow the logger to be called more than once if necessary.
        $this->mockLogger->expects($this->exactly(2))->method('error');

        $client = $this->createDestinationClient();

        // Indirectly confirms no exceptions — any would cause the test to fail.  
        $client->sendAlerts();
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

        $client = $this->createDestinationClient();

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
            $this->createMock(Logger::class),
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

        $client = $this->createDestinationClient();

        $client->sendRequest('<xml></xml>');
    }

    /**
     * Provide a client instance for tests
     *
     * @return DestinationClient $client
     */
    private function createDestinationClient()
    {
        return new DestinationClient(
            $this->mockLogger,
            $this->mockDatabase,
            $this->mockHttpClient
        );
    }
}
