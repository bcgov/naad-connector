<?php
namespace Bcgov\NaadConnector;

use SimpleXMLElement;

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
     * The current output of the socket. Stored so that multi-part responses can
     * be combined.
     *
     * @var string
     */
    protected string $currentOutput = '';

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
        // Create a TCP/IP socket.
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
            // Enables error reporting for XML functions (used by libxml_get_errors()).
            $previousUseInternalErrorsValue = libxml_use_internal_errors(true);
            
            $this->handleResponse($out);

            // Sets XML error reporting back to its original value.
            libxml_use_internal_errors($previousUseInternalErrorsValue);
        }

        $this->logger('Closing socket');
        socket_close($socket);
        $this->logger('OK.');
        return 1;
    }

    /**
     * Handles a socket response (new data received through the socket).
     *
     * @param string $response A partial or complete XML string.
     *
     * @return bool
     */
    protected function handleResponse( string $response ): bool
    {
        $xml = $this->validateResponse($response);
        
        if (!$xml) {
            return false;
        }

        $xml->registerXPathNamespace('x', 'urn:oasis:names:tc:emergency:cap:1.2');
        if ($this->isHeartbeat($xml)) {
            $this->logger('Heartbeat received.');
        } else {
            $this->logger($this->currentOutput);
            $this->logger('A REAL ALERT!');
        }
        $this->currentOutput = '';
        return true;
    }

    /**
     * Combines multi-part responses and attempts to validate XML.
     *
     * @param string $response A partial or complete XML string.
     *
     * @return bool|SimpleXMLElement Returns SimpleXMLElement on success,
     *                               false otherwise.
     */
    protected function validateResponse( string $response ): bool|SimpleXMLElement
    {
        $this->currentOutput .= $response;
        
        $xml = simplexml_load_string($this->currentOutput);

        // Current output is not a valid XML document.
        if (false === $xml) {
            /**
             * </alert> indicates the end of an alert XML document,
             * clear current output for the next response.
             */
            if (str_ends_with(trim($this->currentOutput), '</alert>')) {
                $this->logger('Invalid XML document received.');
                $this->currentOutput = '';
            } else {
                $this->logger('Invalid or partial XML document received.');
            }
            $this->logXmlErrors();
            return false;
        }

        return $xml;
    }

    /**
     * Determines whether a given SimpleXMLElement is a NAAD heartbeat message.
     *
     * @param SimpleXMLElement $xml XML from NAAD socket.
     *
     * @return boolean True if the XML is a heartbeat message, false otherwise.
     */
    protected function isHeartbeat( SimpleXMLElement $xml ): bool
    {
        $sender = $xml->xpath(
            '/x:alert/x:sender[contains(text(),"NAADS-Heartbeat")]'
        );
        return !empty($sender);
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

    /**
     * Logs XML errors.
     *
     * @return void
     */
    protected function logXmlErrors()
    {
        foreach (libxml_get_errors() as $error) {
            print_r($error->message);
        }
    }
}
