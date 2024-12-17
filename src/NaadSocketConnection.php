<?php
namespace Bcgov\NaadConnector;

use Exception;
use Monolog\Logger;
use React\Socket\{
    ConnectionInterface,
    ConnectorInterface
};

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

    /**
     * Constructor for NaadClient.
     *
     * @param string            $socketUrl         The URL of the NAAD socket to
     *                                             connect to.
     * @param NaadSocketClient  $socketClient      An instance of NaadSocketClient.
     * @param Logger            $logger            An instance of Monolog/Logger.
     * @param integer           $port              The port of the NAAD socket to
     *                                             connect to.
     */
    public function __construct(
        string $socketUrl,
        ConnectorInterface $connector,
        NaadSocketClient $socketClient,
        Logger $logger,
        int $port = 8080,
    ) {
        $this->address      = $socketUrl;
        $this->connector    = $connector;
        $this->socketClient = $socketClient;
        $this->logger       = $logger;
        $this->port         = $port;
    }

    /**
     * Connects to the NAAD socket at the given URL and listens.
     *
     * @return int An exit code.
     */
    public function connect(): int
    {
        // Create a TCP/IP socket.
        $this->logger->info('Connecting to socket.');
        $fullAddress = sprintf('%s:%d', $this->address, $this->port);
        $this->connector->connect($fullAddress)->then(function (ConnectionInterface $connection) {
            $this->logger->info('Socket connected.');
            $this->logger->info('Listening for socket messages...');
            $connection->on('data', function($chunk) {
                // Enables error XML error reporting (used by libxml_get_errors()).
                $previousUseInternalErrorsValue = libxml_use_internal_errors(true);
    
                $this->socketClient->handleResponse($chunk);
    
                // Sets XML error reporting back to its original value.
                libxml_use_internal_errors($previousUseInternalErrorsValue);
            });
        }, function (Exception $e) {
            $this->logger->critical('Could not connect to socket: ' . $e->getMessage());
            return 0;
        });

        $this->logger->info('Socket closed.');
        return 1;
    }
}