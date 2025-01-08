<?php
namespace Bcgov\NaadConnector;

use Bcgov\NaadConnector\Entity\Alert;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Monolog\Logger;

/**
 * DestinationClient class makes requests to a destination URL
 * and logs results in the database.
 *
 * @category Client
 * @package  NaadConnector
 * @author   Michael Haswell <Michael.Haswell@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://developer.wordpress.org/rest-api/
 */
class DestinationClient
{

    /**
     * The url of the destination (including full API endpoint).
     *
     * @var string
     */
    protected string $url;

    /**
     * The username of the user to authenticate with.
     *
     * @var string
     */
    protected string $username;

    /**
     * The application password of the user to authenticate with.
     *
     * @var string
     */
    protected string $applicationPassword;

    /**
     * Guzzle HTTP client.
     *
     * @var Client
     */
    protected Client $client;

    /**
     * Monolog Logger.
     *
     * @var Logger
     */
    protected Logger $logger;

    /**
     * The alert Database.
     *
     * @var Database
     */
    protected Database $database;

    /**
     * Constructor for DestinationClient.
     *
     * @param string   $url                 The destination API endpoint.
     * @param string   $username            The username for authentication.
     * @param string   $applicationPassword The password for authentication.
     * @param Logger   $logger              An instance of Monolog/Logger.
     * @param Database $database            Instance of Database for alerts.
     * @param Client   $client              The Guzzle HTTP client (optional).
     */
    public function __construct(
        string $url,
        string $username,
        string $applicationPassword,
        Logger $logger,
        Database $database,
        ?Client $client = null
    ) {
        $this->url                 = $url;
        $this->username            = $username;
        $this->applicationPassword = $applicationPassword;
        $this->logger            = $logger;
        $this->database            = $database;
        $this->client = $client ?? new Client(
            [
                'base_uri' => $this->url,
                'auth'     => [$this->username, $this->applicationPassword],
            ]
        );
    }

    /**
     * Sends unsent alerts to the destination and updates their statuses.
     * 
     * @return bool Returns `true` if all alerts were sent successfully,
     *              `false` if any alert failed to be sent.
     */
    public function sendAlerts(): bool
    {
        $unsentAlerts = $this->database->getUnsentAlerts();
        $allSuccessful = true;
    
        foreach ($unsentAlerts as $alert) {
            try {
                $response = $this->sendRequest($alert->getBody());
                $this->logger->info(
                    'Sent Alert ({id}) to destination.',
                    [ 'id' => $alert->getId() ]
                );
            } catch (\Exception $e) {
                $allSuccessful = false;
                $this->logger->error(
                    'Could not send Alert ({id}): {error}',
                    [ 'id' => $alert->getId(), 'error' => $e->getMessage() ]
                );
            }
                
            if (200 === $response['status_code']) {
                $alert->setSuccess(true);
            } else {
                $alert->incrementFailures();
                $alert->setSuccess(false);
                $allSuccessful = false;
                $this->logger->error(
                    'HTTP response for Alert ({id}): Status {code}: {body}',
                    [
                        'code' => $response['status_code'],
                        'body' => $response['body'],
                        'id' => $alert->getId()
                    ]
                );
            }

            $alert->setSendAttempted(new \DateTime());
            try {
                $this->database->updateAlert($alert);
            } catch (\Exception $e) {
                $allSuccessful = false;
                $this->logger->critical(
                    'Could not update Alert ({id}): {error}',
                    [ 'id' => $alert->getId(), 'error' => $e->getMessage() ]
                );
                throw $e;
            }
        }
        return $allSuccessful;
    }

    /**
     * Sends an alert request to the destination and returns the response.
     *
     * @param string $xml Raw XML string from the alert.
     *
     * @return array Returns an associative array with the following keys:
     *               - 'status_code' (int): The HTTP status code of the response.
     *               - 'body' (string): The body of the response.
     *
     * @throws RequestException If the request fails and no response is available.
     */
    public function sendRequest(string $xml): array
    {
        try {
            $response = $this->client->post(
                '', [
                    'json'    => ['xml' => $xml],
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ]
            );
    
            return [
                'status_code' => $response->getStatusCode(),
                'body'        => (string) $response->getBody(),
            ];
        } catch ( ConnectException $e ) {
            return [
                'status_code' => 0,
                'body'        => 'Connection error: ' . $e->getMessage(),
            ];
        } catch (RequestException $e) {
            // Handle other HTTP errors
            if ($e->hasResponse()) {
                return [
                    'status_code' => $e->getResponse()->getStatusCode(),
                    'body'        => (string) $e->getResponse()->getBody(),
                ];
            }

            throw $e;
        }
    }
}