<?php
namespace Bcgov\NaadConnector;

use Bcgov\NaadConnector\Exception\AlertFetchFailureException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;

/**
 * NaadRepositoryClient class makes requests to the NAAD alert repository.
 *
 * @category Client
 * @package  NaadConnector
 * @author   Michael Haswell <Michael.Haswell@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alertsarchive.pelmorex.com/en.php
 */
class NaadRepositoryClient
{

    protected const URL_TEMPLATE = 'https://%s/%s/%sI%s.xml';
    protected const SANITIZE_RULES = ['-' => '_', '+' => 'p', ':' => '_'];

    protected Client $client;
    protected string $baseUrl;
    protected Logger $logger;

    /**
     * Initializes a new instance of the NaadRepositoryClient class.
     *
     * @param Client $client      The Guzzle HTTP client to use.
     * @param string $naadRepoUrl The base URL used to construct an
     *                            URL for the Alerts repo.
     * @param Logger $logger      An instance of Monolog/Logger.
     */
    public function __construct(Client $client, string $naadRepoUrl, Logger $logger)
    {

        if (empty($naadRepoUrl)) {
            throw new \InvalidArgumentException("Base URL cannot be empty");
        }

        $this->client  = $client;
        $this->baseUrl = $naadRepoUrl;
        $this->logger  = $logger;
    }

    /**
     * Fetches an alert from the NAAD alert repository.
     *
     * @param string $id   The Id of the alert.
     * @param string $sent The sender of the alert.
     *
     * @return string The alert response body.
     *
     * @throws Exception if an error occurs during the GET request.
     */
    public function fetchAlert(string $id, string $sent): string
    {
        try {
            $url = $this->constructURL($id, $sent);
            $this->logger->info(
                'Fetching alert ({id}) from {url}',
                ['id' => $id, 'url' => $url]
            );
            $response = $this->client->get($url);
            return $response->getBody()->getContents();
        } catch (RequestException $e) {
            $this->logger->critical(
                'Failed to fetch alert: {message}',
                ['message' => $e->getMessage()]
            );
            throw new AlertFetchFailureException($e);
        }
    }

    /**
     * Constructs the URL using the provide reference
     *
     * @param string $id   The Id of the alert.
     * @param string $sent The sender of the alert.
     *
     * @return string The constructed URL: datestamp, sent, id.
     */
    protected function constructURL(string $id, string $sent): string
    {
        return sprintf(
            self::URL_TEMPLATE,
            $this->baseUrl,
            strtok($sent, 'T'),
            strtr($sent, self::SANITIZE_RULES),
            strtr($id, self::SANITIZE_RULES),
        );
    }

}
