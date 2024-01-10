<?php
namespace Bcgov\NaadConnector;

/**
 * NaadSocketClient class connects to the NAAD socket and logs its output.
 *
 * @category Client
 * @package  NaadConnector
 * @author   Michael Haswell <Michael.Haswell@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 */
class NaadSocketClient
{

    /**
     * The number of bytes to read at once from the socket stream.
     *
     * @var integer
     */
    protected static $MAX_MESSAGE_SIZE = 5000000;

    /**
     * The name of the NAAD connection instance.
     *
     * @var string
     */
    protected string $name;

    /**
     * The URL of the NAAD socket to connect to.
     *
     * @var string
     */
    protected string $address;

    /**
     * The port of the NAAD socket to connect to.
     *
     * @var integer
     */
    protected int $port;

    /**
     * Constructor for NaadClient.
     *
     * @param string  $name The name of the NAAD connection instance.
     * @param string  $url  The URL of the NAAD socket to connect to.
     * @param integer $port The port of the NAAD socket to connect to.
     */
    public function __construct( string $name, string $url, int $port = 8080 )
    {
        $this->name = $name;
        $this->address = $url;
        $this->port = $port;
    }

    /**
     * Connects to the NAAD socket at the given URL and listens.
     *
     * @return int An exit code.
     */
    public function connect(): int
    {
        /* Create a TCP/IP socket. */
        $this->logger('Creating socket');
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false ) {
            $this->logger(
                sprintf(
                    'socket_create() failed: reason: %s',
                    socket_strerror(socket_last_error())
                )
            );
            return 2;
        } else {
            $this->logger('OK.');
        }

        $address = $this->address;
        $port = $this->port;

        $this->logger("Attempting to connect to '$address' on port '$port'...");
        $result = socket_connect($socket, $address, $port);
        if ($result === false ) {
            $this->logger(
                sprintf(
                    "socket_connect() failed.\nReason: (%s) %s",
                    $result,
                    socket_strerror(socket_last_error($socket))
                )
            );
            return 3;
        } else {
            $this->logger('OK.');
        }

        $this->logger('Reading response:');
        while ( $out = socket_read($socket, self::$MAX_MESSAGE_SIZE) ) {
            $this->handleEvent($out);
        }

        $this->logger('Closing socket');
        socket_close($socket);
        $this->logger('OK.');
        return 1;
    }

    /**
     * Handles a socket event (new data received through the socket).
     *
     * @param string $event An XML string.
     *
     * @return void
     */
    protected function handleEvent( string $event )
    {
        $this->logger($event);
    }

    /**
     * Logs a message.
     *
     * @param string $msg The message to log.
     *
     * @return void
     */
    protected function logger( string $msg )
    {
        $s = sprintf('[%s %s] ', $this->name, date('m/d/Y h:i:s a', time()));
        error_log($s . print_r($msg, true));
    }
}
