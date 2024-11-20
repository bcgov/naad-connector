<?php
namespace Bcgov\NaadConnector;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * DestinationClient class makes requests to a destination url.
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
     * Constructor for DestinationClient.
     *
     * @param string $url                 The url of the destination
     *                                    (including full API endpoint).
     * @param string $username            The username of the user to
     *                                    authenticate with.
     * @param string $applicationPassword The application password of
     *                                    the user to authenticate with.
     */
    public function __construct(
        string $url,
        string $username,
        string $applicationPassword
    ) {
        $this->url                 = $url;
        $this->username            = $username;
        $this->applicationPassword = $applicationPassword;

        $this->client = new Client(
            [
            'base_uri' => $this->url,
            'auth'     => [$this->username, $this->applicationPassword],
            ]
        );
    }

    /**
     * Sends an alert request to the destination.
     *
     * @param string $xml Raw XML string from alert.
     *
     * @return string
     */
    public function sendRequest( string $xml ): string
    {
        try {
            $response = $this->client->post(
                '', [
                'json' => ['xml' => $xml],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                ]
            );

            return (string) $response->getBody();
        } catch (RequestException $e) {
            // Log or handle the exception.
            if ($e->hasResponse()) {
                return (string) $e->getResponse()->getBody();
            }

            throw $e;
        }
    }
}
