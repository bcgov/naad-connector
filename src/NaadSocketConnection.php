<?php
namespace Bcgov\NaadConnector;

use Monolog\Logger;

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

    protected static int $MAX_MESSAGE_SIZE = 5000000;

    protected string $name;

    protected string $address;

    protected int $port;

    protected Logger $logger;

    protected DestinationClient $destinationClient;

    protected NaadSocketClient $socketClient;

    /**
     * Constructor for NaadClient.
     *
     * @param string            $name              The name of the NAAD connection
     *                                             instance.
     * @param string            $socketUrl         The URL of the NAAD socket to
     *                                             connect to.
     * @param DestinationClient $destinationClient An instance of DestinationClient.
     * @param NaadSocketClient  $socketClient      An instance of NaadSocketClient.
     * @param Logger            $logger            An instance of Monolog/Logger.
     * @param integer           $port              The port of the NAAD socket to
     *                                             connect to.
     */
    public function __construct(
        string $name,
        string $socketUrl,
        DestinationClient $destinationClient,
        NaadSocketClient $socketClient,
        Logger $logger,
        int $port = 8080,
    ) {
        $this->name              = $name;
        $this->address           = $socketUrl;
        $this->destinationClient = $destinationClient;
        $this->socketClient = $socketClient;
        $this->logger            = $logger;
        $this->port              = $port;
    }

    /**
     * Connects to the NAAD socket at the given URL and listens.
     *
     * @return int An exit code.
     */
    public function connect(): int
    {
        // Create a TCP/IP socket.
        $this->logger->info('Creating socket.');
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false ) {
            $this->logger->error(
                'socket_create() failed: reason: {error}',
                [
                    'error' => socket_strerror(socket_last_error()),
                ]
            );
            return 2;
        } else {
            $this->logger->info('Created socket.');
        }

        $address = $this->address;
        $port    = $this->port;

        $this->logger->info(
            "Attempting to connect to '{address}' on port '{port}'...",
            [
                'address' => $address,
                'port'    => $port,
            ]
        );
        $result = socket_connect($socket, $address, $port);
        if ($result === false ) {
            $error = socket_strerror(socket_last_error($socket));
            $this->logger->error(
                "socket_connect() failed.\nReason: ({result}) {error}",
                [
                    'result' => $result,
                    'error'  => $error,
                ]
            );
            return 3;
        } else {
            $this->logger->info('Connected to socket.');
        }

        $this->logger->info('Listening for socket messages...');
        while ( $out = socket_read($socket, self::$MAX_MESSAGE_SIZE) ) {
            // Enables error XML error reporting (used by libxml_get_errors()).
            $previousUseInternalErrorsValue = libxml_use_internal_errors(true);

            $this->socketClient->handleResponse($out);

            // Sets XML error reporting back to its original value.
            libxml_use_internal_errors($previousUseInternalErrorsValue);
        }

        $this->logger->info('Closing socket.');
        socket_close($socket);
        $this->logger->info('Socket closed.');
        return 1;
    }
}