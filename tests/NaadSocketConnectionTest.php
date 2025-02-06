<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\{
    Test,
    CoversClass,
};
use PHPUnit\Framework\TestCase;
use Monolog\Logger;

use Bcgov\NaadConnector\{
    NaadSocketClient,
    NaadSocketConnection,
};
use React\Promise\Promise;
use React\Socket\ConnectionInterface;
use React\Socket\ConnectorInterface;

/**
 * NaadSocketConnectionTest Class for testing NaadSocketConnection.
 *
 * @category Client
 * @package  NaadConnector
 * @author   Michael Haswell <Michael.Haswell@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 */
#[CoversClass('Bcgov\NaadConnector\NaadSocketConnection')]
final class NaadSocketConnectionTest extends TestCase
{
    protected $connector;
    protected $socketClient;
    protected $logger;
    protected $naadSocketConnection;
    protected $connection;

    /**
     * Sets up dependency mocks and the class to be tested.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->connector = $this->createMock(ConnectorInterface::class);
        $this->socketClient = $this->createMock(NaadSocketClient::class);
        $this->logger = $this->createMock(Logger::class);
        $this->connection = $this->createMock(ConnectionInterface::class);

        $this->naadSocketConnection = new NaadSocketConnection(
            'ws://localhost',
            $this->connector,
            $this->socketClient,
            $this->logger
        );
    }

    #[Test]
    /**
     * Tests a successful connection to socket.
     *
     * @return void
     */
    public function testConnectSuccess()
    {
        $write1 = 'test data 1';
        $write2 = 'test data 2';
        $write3 = 'test data 3';

        $this->connector->method('connect')->willReturn(
            new Promise(
                function ($resolve) {
                    $resolve($this->connection);
                }
            )
        );

        $this->logger
            ->expects($this->exactly(4))
            ->method('info');

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Exception during socket connection:'));

        // SocketClient->handleResponse should be called 3 times with the data
        // from the $write variables.
        $invokedCount = $this->exactly(3);
        $this->socketClient
            ->expects($invokedCount)
            ->method('handleResponse')
            ->willReturnCallback(
                function (string $value) use (
                    $invokedCount,
                    $write1,
                    $write2,
                    $write3
                ) {
                    match ($invokedCount->numberOfInvocations()) {
                        1 => $this->assertEquals($write1, $value),
                        2 => $this->assertEquals($write2, $value),
                        3 => $this->assertEquals($write3, $value),
                    };
                    return true;
                }
            );

        // Simulate receiving data on the connection
        $this->connection->method('on')->willReturnCallback(
            function ($event, $callback) use ($write1, $write2, $write3) {
                // Data (write) event.
                if ($event === 'data') {
                    $callback($write1);
                    $callback($write2);
                    $callback($write3);
                }

                if ($event === 'end') {
                    $callback();
                }

                if ($event === 'close') {
                    $callback();
                }

                if ($event === 'error') {
                    $callback(new Exception('Test exception'));
                }
            }
        );

        $exitCode = $this->naadSocketConnection->connect();
        $this->assertEquals(1, $exitCode);
    }

    #[Test]
    /**
     * Tests a failure to connect to socket.
     *
     * @return void
     */
    public function testConnectFailure()
    {
        $this->connector->method('connect')->willReturn(
            new Promise(
                function ($resolve, $reject) {
                    $reject(new \Exception('Connection failed'));
                }
            )
        );

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('Could not connect to socket:'));

        $exitCode = $this->naadSocketConnection->connect();
        $this->assertEquals(1, $exitCode);
    }
}