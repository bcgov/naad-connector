<?php
namespace Bcgov\NaadConnector;

use Bcgov\NaadConnector\Exception\AlertFailureThresholdException;
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
    const FAILURE_THRESHOLD = 5;
    private const FATAL_ERRORS = [401, 403, 404];
    protected Client $client;
    protected Logger $logger;
    protected Database $database;

    /**
     * Constructor for DestinationClient.
     *
     * @param Logger   $logger   An instance of Monolog/Logger.
     * @param Database $database Instance of Database for alerts.
     * @param Client   $client   An instance of a guzzle client with:
     *                           auth, url, and headers.
     */
    public function __construct(
        Logger $logger,
        Database $database,
        Client $client,
    ) {
        $this->logger              = $logger;
        $this->database            = $database;
        $this->client              = $client;
    }

    /**
     * Sends unsent alerts to the destination and updates their statuses.
     *
     * @return bool Returns `true` if all alerts were sent successfully,
     *              `false` if any alert failed to be sent.
     * @throws Exception If sending an alert fails and its failure threshold
     *                   was reached.
     */
    public function sendAlerts(): bool
    {
        $unsentAlerts = $this->database->getUnsentAlerts();
        $allSuccessful = true;

        foreach ($unsentAlerts as $alert) {
            try {
                $response = $this->sendRequest($alert->getBody());

                if (200 === $response['status_code']) {
                    $alert->setSuccess(true);
                    $this->logger->info(
                        'Sent Alert ({id}) to destination.',
                        ['id' => $alert->getId()]
                    );

                    // If event page was created log at info-level, otherwise debug.
                    $level = 'debug';
                    if (str_contains(
                        $response['body'],
                        'Event created successfully'
                    )
                    ) {
                        $level = 'info';
                    }

                    $this->logger->log(
                        $level,
                        'Response: {body}',
                        ['body' => $response['body']]
                    );
                } elseif (500 <= $response['status_code']
                    || in_array($response['status_code'], self::FATAL_ERRORS, true)
                ) {
                    // 5xx and certain 4xx errors require intervention.
                    throw new \Exception('Unexpected status code received.');

                } else {
                    // All other response codes should just be logged.
                    $allSuccessful = false;
                    $this->handleAlertFailure($alert, $response);
                }
            } catch (\Exception $e) {
                $allSuccessful = false;
                $this->handleAlertFailure($alert, $response, $e);

                // Throw exception when failure threshold is reached.
                if ($alert->getFailures() >= self::FAILURE_THRESHOLD) {
                    throw new AlertFailureThresholdException(
                        self::FAILURE_THRESHOLD,
                        $alert->getId(),
                        $e
                    );
                }
            } finally {
                $alert->setSendAttempted(new \DateTime());
                $this->database->flush();
            }
        }
        return $allSuccessful;
    }

    /**
     * Log an HTTP response to a failed attempt to send an alert.
     *
     * @param Alert          $alert    The Alert in the failed send attempt.
     * @param array|null     $response The HTTP response from WordPress, if any.
     * @param Exception|null $e        The provided exception, if any.
     *
     * @return void
     */
    private function handleAlertFailure(
        $alert, ?array $response = null, ?\Exception $e = null
    ): void {

        $alert->incrementFailures();
        $alert->setSuccess(false);

        if ($e) {
            $this->logger->error(
                'Could not send Alert ({id}): {error}',
                ['id' => $alert->getId(), 'error' => $e->getMessage()]
            );
        } else {
            $this->logger->error(
                'Could not send Alert ({id})',
                ['id' => $alert->getId()]
            );
        }

        if (isset($response)) {
            $this->logger->error(
                'HTTP response for Alert ({id}): Status {code}: {body}',
                [
                    'code' => $response['status_code'],
                    'body' => $response['body'],
                    'id' => $alert->getId()
                ]
            );
        }
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
        $options = [ 'json' => ['xml' => $xml]];

        try {
            $response = $this->client->post('', $options);

            // log Request and Response headers when the log level is set to 'debug'
            $this->logger->debug(
                'Request Headers: ',
                // TODO: Replace as getConfig() is deprecated.
                [$this->client->getConfig('headers')]
            );
            $this->logger->debug(
                'Response Headers: ',
                [$response->getHeaders()]
            );

            return [
                'status_code' => $response->getStatusCode(),
                'body'        => (string) $response->getBody(),
                'headers'     => $response->getHeaders(),
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
