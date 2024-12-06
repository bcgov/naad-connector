<?php
namespace Bcgov\NaadConnector;

use Bcgov\NaadConnector\Database;
use Bcgov\NaadConnector\Entity\Alert;
use Bcgov\NaadConnector\NaadVars;
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
        NaadRepositoryClient $repositoryClient = null
    ) {
        $this->name              = $name;
        $this->destinationClient = $destinationClient;
        $this->logger            = $logger;
        $this->database          = $database;
        $this->repositoryClient  = $repositoryClient ?? new NaadRepositoryClient();
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
        $naadVars = new NaadVars();
        $xml = $this->validateResponse($response);

        if (! $xml ) {
            return false;
        }

        $xml->registerXPathNamespace('x', self::XML_NAMESPACE);

        if ($this->isHeartbeat($xml) ) {
            $this->logger->info('Heartbeat received.');
            $this->touchHeartbeatFile();
            $missedAlerts = $this->findMissedAlerts($xml);
            if (count($missedAlerts) > 0 ) {
                $repoUrl = $naadVars->naadRepoUrl;
                $this->logger->info(
                    'Found {count} missing alerts in heartbeat. '
                        . 'Fetching from NAAD repository ({repoUrl}).',
                    [ 'count' => count($missedAlerts), 'repoUrl' => $repoUrl ]
                );
                foreach ( $missedAlerts as $alert ) {
                    $this->currentOutput = '';
                    $rawXml                 = $this->repositoryClient->fetchAlert(
                        $alert
                    );
                    $xml = $this->validateResponse($rawXml);
                    if ($xml) {
                        $result = $this->insertAlert($xml);
                    }
                }
            }
        } else {
            $this->insertAlert($xml);
            $result = $this->destinationClient->sendRequest($this->currentOutput);
            $this->logger->info(
                '{result}',
                [
                    'result' => $result,
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
    protected function insertAlert( SimpleXMLElement $xml )
    {
        $alert = null;

        try {
            $alert = Alert::fromXml($xml);
        } catch(Exception $e) {
            $alertId = $alert ? $alert->getId() : 'unknown';
            $this->logger->critical($e->getMessage());
            $this->logger->critical(
                'Could not parse alert XML.'
            );
            throw $e;
        }
        try {
            $this->database->insertAlert($alert);
        } catch ( Exception $e ) {
            $alertId = $alert ? $alert->getId() : 'unknown';
            $this->logger->critical($e->getMessage());
            $this->logger->critical(
                'Could not connect to database or insert Alert ({id}).',
                [ 'id' => $alertId ]
            );
            throw $e;
        }
        $this->logger->info('Inserted Alert ({id}).', [ 'id' => $alert->getId() ]);
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

        // Validate the XML namespace.
        if (!$this->isValidNamespace($xml)) {
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
     * Validates the XML namespace.
     *
     * @param SimpleXMLElement $xml The XML element to check.

     * @return bool Returns true if the namespace is valid, false otherwise.
     */
    private function isValidNamespace(SimpleXMLElement $xml): bool
    {
        $namespaces = $xml->getNamespaces();
        $currentNamespace = $namespaces[''];

        if (self::XML_NAMESPACE !== $currentNamespace) {
            $this->logger->info(
                "Unexpected namespace: {capNamespace}. Expected: {xmlNamespace}.",
                [
                    'capNamespace' => $currentNamespace,
                    'xmlNamespace' => self::XML_NAMESPACE,
                ]
            );
            return false;
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
            $this->logger->critical($e->getMessage());
            $this->logger->critical(
                'Could not retrieve existing alerts from database.'
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