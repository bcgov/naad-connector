<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\{
    Test,
    CoversClass,
    DataProvider,
    UsesClass
};
use PHPUnit\Framework\TestCase;

use Bcgov\NaadConnector\{
    CustomLogger,
    NaadSocketClient,
    NaadSocketConnection,
};
use React\Promise\Promise;
use React\Socket\ConnectorInterface;
use React\Socket\Server;
use React\Socket\SocketServer;
use React\Stream\DuplexResourceStream;

/**
 * NaadSocketClientTest Class for testing NaadSocketClient.
 * Uses the \Entity\Alert class.
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

    #[Test]
    public function testConnect()
    {
        $socketClient = $this->createStub(NaadSocketClient::class);
        $logger = $this->createStub(CustomLogger::class);

        $connector = $this->createMock(ConnectorInterface::class);
        $connector
            ->expects($this->once())
            ->method('connect')
            ->with('test:1000')
            ->willReturn(new Promise(function () { }));

        $client = new NaadSocketConnection(
            'test',
            $connector,
            $socketClient,
            $logger,
            1000
        );

        $result = $client->connect();

        $this->assertEquals(1, $result);
    }

    public function testConnectFailure()
    {
        $socketClient = $this->createMock(NaadSocketClient::class);
        $socketClient
            ->expects($this->once())
            ->method('handleResponse')
            ->with('test');

        $logger = $this->createStub(CustomLogger::class);

        $s = fopen('php://memory', 'r+');
        $stream = new DuplexResourceStream($s);
        $connector = $this->createMock(ConnectorInterface::class);
        $connector
            ->expects($this->once())
            ->method('connect')
            ->with('test:1000')
            ->willReturn(new Promise(
                function ($resolve, $reject) use($stream) {
                    $buffer = 'test';
                    $stream->on('data', function ($chunk) use (&$buffer) {
                        $buffer .= $chunk;
                    });
    
                    $stream->on('error', $reject);
    
                    $stream->on('close', function () use (&$buffer, $resolve) {
                        $resolve($buffer);
                    });
                },
                function () {
                    throw new \RuntimeException();
                }
            ));

        $client = new NaadSocketConnection(
            'test',
            $connector,
            $socketClient,
            $logger,
            1000
        );

        $result = $client->connect();

        $this->assertEquals(1, $result);
    }
}
