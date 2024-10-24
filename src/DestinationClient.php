<?php
namespace Bcgov\NaadConnector;

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
        // Open curl connection.
        $curl = curl_init();

        // Set method to POST, add XML to payload.
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([ 'xml' => $xml ]));
        curl_setopt($curl, CURLOPT_HTTPHEADER, [ 'Content-Type:application/json' ]);

        // Set authentication using username and application password.
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt(
            $curl,
            CURLOPT_USERPWD,
            sprintf('%s:%s', $this->username, $this->applicationPassword)
        );

        // Set destination url.
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        // POST to destination.
        $result = curl_exec($curl);
        print_r(curl_error($curl));

        // Close curl connection.
        curl_close($curl);

        return $result;
    }
}
