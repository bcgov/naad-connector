<?php
namespace Bcgov\NaadConnector;

use Bcgov\NaadConnector\Database;
use Bcgov\NaadConnector\Entity\Alert;
use Monolog\Logger;
use SimpleXMLElement;
use Exception;

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
     * @var int
     */
    protected static int $MAX_MESSAGE_SIZE = 5000000;

    /**
     * The expected XML namespace of alerts, referring to the CAP 1.2 schema.
     *
     * @link https://docs.oasis-open.org/emergency/cap/v1.2/CAP-v1.2-os.html
     *
     * @var string
     */
    protected static string $XML_NAMESPACE = 'urn:oasis:names:tc:emergency:cap:1.2';

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
     * An instance of DestinationClient.
     *
     * @var DestinationClient
     */
    protected DestinationClient $destinationClient;

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
     * The monolog channel for saving to stream or file.
     *
     * @var Logger
     */
    protected Logger $logger;

    /**
     * The Database class that handles connection setup and returns
     * a Doctrine EntityManager instance.
     *
     * @var Database
     */
    protected Database $database;

    /**
     * Constructor for NaadClient.
     *
     * @param string            $name              The name of the NAAD connection
     *                                             instance.
     * @param string            $socketUrl         The URL of the NAAD socket to
     *                                             connect to.
     * @param DestinationClient $destinationClient An instance of DestinationClient
     *                                             to handle making requests to a
     *                                             destination.
     * @param Logger            $logger            An instance of Monolog/Logger.
     * @param Database          $database          An instance of Database.
     * @param integer           $port              The port of the NAAD socket to
     *                                             connect to.
     */
    public function __construct(
        string $name,
        string $socketUrl,
        DestinationClient $destinationClient,
        Logger $logger,
        Database $database,
        int $port = 8080,
    ) {
        $this->name = $name;
        $this->address = $socketUrl;
        $this->destinationClient = $destinationClient;
        $this->logger = $logger;
        $this->database = $database;
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
        $this->logger->info('Creating socket');
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false ) {
            $this->logger->error(
                'socket_create() failed: reason: {error}',
                [
                    'error'=>socket_strerror(socket_last_error()),
                ]
            );
            return 2;
        } else {
            $this->logger->info('OK.');
        }

        $address = $this->address;
        $port = $this->port;

        $this->logger->info(
            "Attempting to connect to '{address}' on port '{port}'...",
            [
                'address'=> $address, 'port' =>$port,
            ]
        );
        $result = socket_connect($socket, $address, $port);
        if ($result === false ) {
            $error = socket_strerror(socket_last_error($socket));
            $this->logger->error(
                "socket_connect() failed.\nReason: ({result}) {error}",
                [
                    'result'=> $result,
                    'error'=> $error
                ]
            );
            return 3;
        } else {
            $this->logger->info('OK.');
        }

        $this->logger->info('Reading response:');
        while ( $out = socket_read($socket, self::$MAX_MESSAGE_SIZE) ) {
            // Enables error XML error reporting (used by libxml_get_errors()).
            $previousUseInternalErrorsValue = libxml_use_internal_errors(true);
            
            $this->handleResponse($out);

            // Sets XML error reporting back to its original value.
            libxml_use_internal_errors($previousUseInternalErrorsValue);
        }

        $this->logger->info('Closing socket');
        socket_close($socket);
        $this->logger->info('OK.');
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

        $xml->registerXPathNamespace('x', self::$XML_NAMESPACE);

        if ($this->isHeartbeat($xml)) {
            $this->logger->info('Heartbeat received.');
        } else {
            $this->insertAlert($xml);
            $result = $this->destinationClient->sendRequest($this->currentOutput);
            $this->logger->info(
                "{result}",
                [
                    'result'=>$result
                ]
            );
        }
        $this->currentOutput = '';
        return true;
    }

    /**
     * Inserts an Alert into the database.
     *
     * @param SimpleXMLElement $xml XML of the Alert.
     *
     * @return void
     */
    protected function insertAlert(SimpleXMLElement $xml)
    {
        try {
            $alert = Alert::fromXml($xml);
            $entityManager = $this->database::getEntityManager();
            $entityManager->persist($alert);
            $entityManager->flush();
        } catch(Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->logger->critical(
                'Could not connect to database or insert Alert ({id}).',
                ['id' => $alert->getId()]
            );
            exit(1);
        }
        $this->logger->info('Inserted Alert ({id}).', ['id' => $alert->getId()]);
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
                $this->logger->info('Invalid XML document received.');
                $this->currentOutput = '';
            } else {
                $this->logger->info('Invalid or partial XML document received.');
            }
            $this->logXmlErrors();
            return false;
        }

        // If XML does not have the correct namespace, return false.
        $namespaces = $xml->getNamespaces();
        $capNamespace = $namespaces[""];
        if (self::$XML_NAMESPACE !== $capNamespace) {
            $this->logger->info(
                "Unexpected namespace '{capNamespace}'.
                Expecting namespace '{xmlNamespace}'.",
                [
                    'capNamespace'=>$capNamespace,
                    'xmlNamespace'=>self::$XML_NAMESPACE 
                ]
            );
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
     * Logs XML errors.
     *
     * @return void
     */
    protected function logXmlErrors()
    {
        foreach (libxml_get_errors() as $error) {
            $this->logger->info($error->message);
        }
    }
}
