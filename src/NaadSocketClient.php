<?php
namespace Bcgov\NaadConnector;

use Bcgov\NaadConnector\Database;
use Bcgov\NaadConnector\Entity\Alert;
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
    protected static string $XML_NAMESPACE = 'urn:oasis:names:tc:emergency:cap:1.2';

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
        $xml = $this->validateResponse($response);

        if (! $xml ) {
            return false;
        }

        $xml->registerXPathNamespace('x', self::$XML_NAMESPACE);

        if ($this->isHeartbeat($xml) ) {
            $this->logger->info('Heartbeat received.');
            $missedAlerts = $this->findMissedAlerts($xml);
            if (count($missedAlerts) > 0 ) {
                $repoUrl = getenv('NAAD_REPO_URL');
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
        try {
            $alert = Alert::fromXml($xml);
            $this->database->insertAlert($alert);
        } catch ( Exception $e ) {
            $this->logger->critical($e->getMessage());
            $this->logger->critical(
                'Could not connect to database or insert Alert ({id}).',
                [ 'id' => $alert->getId() ]
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
    protected function validateResponse( string $response ): bool|SimpleXMLElement
    {
        $this->currentOutput .= $response;

        $xml = simplexml_load_string($this->currentOutput);

        // Current output is not a valid XML document.
        if (false === $xml ) {
            /**
             * </alert> indicates the end of an alert XML document,
             * clear current output for the next response.
             */
            if (str_ends_with(trim($this->currentOutput), '</alert>') ) {
                $this->logger->error('Invalid XML document received.');
                $this->currentOutput = '';
            } else {
                $this->logger->debug(
                    'Partial XML document received. ' . 
                    'Attempting to build complete alert.'
                );
            }
            $this->logXmlErrors();
            return false;
        }

        // If XML does not have the correct namespace, return false.
        $namespaces   = $xml->getNamespaces();
        $capNamespace = $namespaces[''];
        if (self::$XML_NAMESPACE !== $capNamespace ) {
            $this->logger->info(
                "Unexpected namespace '{capNamespace}'.
                Expecting namespace '{xmlNamespace}'.",
                [
                    'capNamespace' => $capNamespace,
                    'xmlNamespace' => self::$XML_NAMESPACE,
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
        return ! empty($sender);
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
        $existingAlerts   = $this->database->getAlertsById(
            array_column($references, 'id')
        );
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
