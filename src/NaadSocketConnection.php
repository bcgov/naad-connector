<?php
namespace Bcgov\NaadConnector;

use Exception;
use Monolog\Logger;
use React\Socket\{
    ConnectionInterface,
    ConnectorInterface
};
use React\EventLoop\LoopInterface;

/**
 * NaadSocketConnection class connects to the NAAD socket.
 *
 * @category Client
 * @package  NaadConnector
 * @author   Michael Haswell <Michael.Haswell@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 */
class NaadSocketConnection
{
    protected string $address;

    protected int $port;

    protected Logger $logger;

    protected NaadSocketClient $socketClient;

    protected ConnectorInterface $connector;

    protected int $reconnectAttempts = 0;

    protected int $maxReconnectAttempts = 5;

    /**
     * Constructor for NaadClient.
     *
     * @param string             $socketUrl    The URL of the NAAD socket to
     *                                         connect to.
     * @param ConnectorInterface $connector    The React/Socket Connector to
     *                                         connect with.
     * @param NaadSocketClient   $socketClient An instance of NaadSocketClient.
     * @param Logger             $logger       An instance of Monolog/Logger.
     * @param LoopInterface      $loop         An instance of React/EventLoop.
     * @param integer            $port         The port of the NAAD socket to
     *                                         connect to.
     */
    public function __construct(
        string $socketUrl,
        ConnectorInterface $connector,
        NaadSocketClient $socketClient,
        Logger $logger,
        LoopInterface $loop,
        int $port = 8080,
    ) {
        $this->address      = $socketUrl;
        $this->connector    = $connector;
        $this->socketClient = $socketClient;
        $this->logger       = $logger;
        $this->port         = $port;
        $this->loop         = $loop;
    }

    /**
     * Connects to the NAAD socket at the given URL and listens.
     *
     * @return int An exit code.
     */
    public function connect(): int
    {
        $this->logger->info(
            "Attempting to connect to '{address}' on port '{port}'...",
            [
                'address' => $this->address,
                'port'    => $this->port,
            ]
        );
        $fullAddress = sprintf('%s:%d', $this->address, $this->port);
        $this->connector->connect($fullAddress)->then(
            // Successful connection, get a Connection object.
            function (ConnectionInterface $connection) {
                $this->logger->info(
                    'Socket connected. Listening for socket messages...'
                );
    
                // Set up a periodic ping every 30 seconds.
                $this->loop->addPeriodicTimer(
                    30, function () use ($connection) {
                        $connection->write("\n");
                    }
                );
    
                $this->setEventHandlers($connection);
                $this->loop->run();
            },
            // Unsuccessful connection, get an Exception.
            function (Exception $e) {
                $this->logger->critical(
                    'Could not connect to socket: ' . $e->getMessage()
                );
                return 0;
            }
        );

        return 1;
    }

    /**
     * Sets up handlers for socket connection events.
     *
     * @param ConnectionInterface $connection React/Socket Connection to set
     *                                        event handlers for.
     * 
     * @return void
     * @link   https://github.com/reactphp/stream?tab=readme-ov-file#readablestreaminterface
     */
    protected function setEventHandlers(ConnectionInterface $connection)
    {
        $this->reconnectAttempts = 0;

        $connection->on(
            'data', function (string $chunk) {
                // Enables error XML error reporting (used by libxml_get_errors()).
                $previousUseInternalErrorsValue = libxml_use_internal_errors(true);

                $this->socketClient->handleResponse($chunk);

                // Sets XML error reporting back to its original value.
                libxml_use_internal_errors($previousUseInternalErrorsValue);
            }
        );

        $connection->on(
            'close', function () {
                $this->reconnectAttempts++;

                if ($this->reconnectAttempts >= $this->maxReconnectAttempts ) {
                    $this->logger->critical(
                        'Socket closed after {reconnectAttempts} attempts. Exiting.',
                        ['reconnectAttempts' => $this->reconnectAttempts]
                    );
                    exit(1);
                }

                $this->logger->info('Socket closed.');
                $this->loop->addTimer(
                    5, function () {
                        $this->connect();
                    }
                );
            }
        );

        $connection->on(
            'end', function () {
                $this->logger->info('Socket ended.');
            }
        );

        $connection->on(
            'error', function (Exception $e) {
                $this->logger->error(
                    'Exception during socket connection: ' . $e->getMessage()
                );
            }
        );
    }
}