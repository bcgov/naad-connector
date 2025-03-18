<?php
namespace Bcgov\NaadConnector;

use Bcgov\NaadConnector\Database;
use Bcgov\NaadConnector\Entity\Alert;
use DOMDocument;
use Monolog\Logger;
use SimpleXMLElement;
use Exception;

/**
 * NaadSocketClient class handles responses from the NAAD socket and logs its output.
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
     * The expected XML namespace of alerts, referring to the CAP 1.2 schema.
     *
     * @link https://docs.oasis-open.org/emergency/cap/v1.2/CAP-v1.2-os.html
     *
     * @var string
     */
    protected const XML_NAMESPACE = 'urn:oasis:names:tc:emergency:cap:1.2';

    /**
     * The name of the schema file to use for validation of alert XML documents.
     * This should correspond to an .xsd file in the schema/ directory.
     *
     * @var string
     */
    protected const SCHEMA_NAME = 'cap12';

    protected const HEARTBEAT_FILE_PATH = 'heartbeat.log';

    /**
     * The name of the NAAD connection instance.
     *
     * @var string
     */
    protected string $name;

    protected DestinationClient $destinationClient;

    protected string $currentOutput = '';

    protected Logger $logger;

    protected Database $database;

    protected NaadRepositoryClient $repositoryClient;

    /**
     * Constructor for NaadClient.
     *
     * @param string               $name              The name of the NAAD connection
     *                                                instance.
     * @param DestinationClient    $destinationClient An instance of
     *                                                DestinationClient.
     * @param Logger               $logger            An instance of Monolog/Logger.
     * @param Database             $database          An instance of Database.
     * @param NaadRepositoryClient $repositoryClient  An instance of
     *                                                NaadRepositoryClient.
     */
    public function __construct(
        string $name,
        DestinationClient $destinationClient,
        Logger $logger,
        Database $database,
        NaadRepositoryClient $repositoryClient,
    ) {
        $this->name              = $name;
        $this->destinationClient = $destinationClient;
        $this->logger            = $logger;
        $this->database          = $database;
        $this->repositoryClient  = $repositoryClient;
    }

    /**
     * Handles a socket response (new data received through the socket).
     *
     * @param string $response A partial or complete XML string.
     *
     * @return bool
     */
    public function handleResponse( string $response ): bool
    {
        $xml = $this->validateResponse($response);

        if (! $xml ) {
            return false;
        }

        $xml->registerXPathNamespace('x', self::XML_NAMESPACE);

        if ($this->isHeartbeat($xml) ) {
            $this->logger->debug('Heartbeat received.');
            $this->touchHeartbeatFile();
            $missedAlerts = $this->findMissedAlerts($xml);
            if (count($missedAlerts) > 0) {
                $this->logger->info(
                    'Found {count} missing alerts in heartbeat. '
                        . 'Fetching from NAAD repository.',
                    ['count' => count($missedAlerts)]
                );

                // Fetch, validate, then process missed alerts.
                foreach ( $missedAlerts as $alert ) {
                    $this->currentOutput = '';
                    $rawXml = $this->repositoryClient->fetchAlert(
                        $alert['id'], $alert['sent']
                    );
                    $xml = $this->validateResponse($rawXml);
                    if ($xml) {
                        $this->processAlert($xml);
                    }
                }
            }
            $shouldSendAlerts = true;
        } else {
            $shouldSendAlerts = $this->processAlert($xml);
        }

        // Try to send alerts to the destination.
        if ($shouldSendAlerts) {
            try {
                $this->destinationClient->sendAlerts();
            } catch (Exception $e) {
                $this->logger->critical(
                    'Could not update alerts: {error}',
                    [ 'error' => $e->getMessage() ]
                );
                throw $e;
            }
        } else {
            $this->logger->debug(
                'Skipping send (another instance received the alert first).'
            );
        }

        $this->currentOutput = '';
        return true;
    }

    /**
     * Processes an alert by sending it to the destination
     * and inserting it into the database.
     *
     * This method:
     * - Parses the alert from the given XML.
     * - Sends the alert to the configured destination using the DestinationClient.
     * - Updates the alert's success status and failure count based on the response.
     * - Inserts the updated alert into the database.
     *
     * @param SimpleXMLElement $xml The XML representation of the alert.
     *
     * @return void
     *
     * @throws \Exception If the alert cannot be parsed
     * or inserted into the database.
     */
    protected function processAlert( SimpleXMLElement $xml ): bool
    {
        // Try to parse the XML into an alert.
        try {
            $alert = Alert::fromXml($xml);
        } catch ( Exception $e ) {
            $this->logger->critical(
                'Could not parse alert XML: ' . $e->getMessage()
            );
            throw $e;
        }

        // Try to insert the alert into the database.
        try {
            $insertedSuccessfully = $this->database->insertAlert($alert);
            $this->logger->info(
                'Inserted Alert ({id}).',
                [ 'id' => $alert->getId() ]
            );
            return $insertedSuccessfully;
        } catch (Exception $e) {
            $this->logger->critical(
                'Could not connect to database or insert Alert ({id}): {error}',
                [ 'id' => $alert->getId(), 'error' => $e->getMessage() ]
            );
            throw $e;
        }
    }

    /**
     * Combines multi-part responses and attempts to validate XML.
     *
     * @param string $response A partial or complete XML string.
     *
     * @return bool|SimpleXMLElement Returns SimpleXMLElement on success,
     *                               false otherwise.
     */
    protected function validateResponse(string $response): bool|SimpleXMLElement
    {
        // Return false if the response is null or empty.
        if (empty($response) ) {
            return false;
        }

        // Append the response to the current output.
        $this->currentOutput .= $response;

        // Attempt to load the current output as XML.
        $xml = simplexml_load_string($this->currentOutput);

        // Current output is not a valid XML document.
        if (false === $xml ) {
            return $this->handleInvalidXml();
        }

        if (!$this->isValidSchema($xml)) {
            return false;
        }

        return $xml; // Return the valid SimpleXMLElement.
    }

    /**
     * Handles the case where the XML is invalid.
     *
     * @return bool Returns false after logging the error.
     */
    protected function handleInvalidXml(): bool
    {
        // Check if the current output ends with the closing alert tag.
        if (str_ends_with(trim($this->currentOutput), '</alert>')) {
            $this->logger->error('Invalid XML document received.');
            $this->currentOutput = ''; // Clear output for the next response.
        } else {
            $this->logger->debug(
                'Partial XML document received. Attempting to build complete alert.'
            );
        }

        // Log XML errors for further debugging.
        $this->logXmlErrors();

        return false;
    }

    /**
     * Validates an alert xml against the schema.
     * If the alert has a namespace that doesn't match the expected schema namespace
     * skip validation and treat the document as valid.
     *
     * @param SimpleXMLElement $xml Alert xml.
     *
     * @return boolean
     */
    protected function isValidSchema(SimpleXMLElement $xml): bool
    {
        $namespaces = $xml->getNamespaces();
        if (array_search(self::XML_NAMESPACE, $namespaces) !== false) {
            // Load XML into DOMDocument class.
            $domDocument = new DOMDocument();
            $domDocument->loadXml($this->currentOutput);

            // Validate schema.
            $schemaPath = sprintf('schema/%s.xsd', self::SCHEMA_NAME);
            if (!$domDocument->schemaValidate($schemaPath)) {
                return false;
            }
        } else {
            $this->logger->warning(
                'Unknown namespace received, skipping schema validation. '
                . 'Expected "{selfNamespace}".',
                [
                    'selfNamespace' => self::XML_NAMESPACE,
                ]
            );
        }
        return true;
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
        return ! empty($sender);
    }

    /**
     * Touches the heartbeat file in order to set its last modified date for
     * liveness probe.
     *
     * @return void
     */
    protected function touchHeartbeatFile()
    {
        touch(self::HEARTBEAT_FILE_PATH);
    }

    /**
     * Finds any alerts that were missed by the socket connection.
     *
     * @param SimpleXmlElement $xml Heartbeat XML.
     *
     * @return array An array containing the heartbeat references for any alerts
     *               that are not already in the database.
     */
    protected function findMissedAlerts( SimpleXMLElement $xml ): array
    {
        $rawReferences = explode(' ', $xml->references);
        $references    = [];

        // Separate the references value into sender, id, and sent parts.
        foreach ( $rawReferences as $rawReference ) {
            $referenceParts = explode(',', $rawReference);
            $references[]   = [
                'sender' => $referenceParts[0],
                'id'     => $referenceParts[1],
                'sent'   => $referenceParts[2],
            ];
        }

        // Remove any reference ids that already exist in the database.
        try {
            $existingAlerts   = $this->database->getAlertsById(
                array_column($references, 'id')
            );
        } catch (Exception $e) {
            $this->logger->critical(
                'Error retrieving alerts from database: {message}',
                ['message' => $e->getMessage()]
            );
            throw $e;
        }
        $existingAlertIds = array_map(
            function ( $alert ) {
                return $alert->getId();
            },
            $existingAlerts
        );
        foreach ( $references as $key => $reference ) {
            if (in_array($reference['id'], $existingAlertIds) ) {
                unset($references[ $key ]);
            }
        }
        return $references;
    }

    /**
     * Logs XML errors.
     *
     * @return void
     */
    protected function logXmlErrors()
    {
        foreach ( libxml_get_errors() as $error ) {
            $this->logger->debug($error->message);
        }
    }
}