<?php
namespace Bcgov\NaadConnector;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;


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

    protected const URL_TEMPLATE = 'http://%s/%s/%sI%s.xml';
    protected const SANITIZE_RULES = ['-' => '_', '+' => 'p', ':' => '_'];

    protected Client $client;
    protected string $baseUrl;

    /**
     * Initializes a new instance of the NaadRepositoryClient class.
     *
     * @param Client $client      - The Guzzle HTTP client to use.
     * @param string $naadRepoUrl - The base URL used to construct an
     *                            URL for the Alerts repo.
     */
    public function __construct(Client $client, string $naadRepoUrl)
    {

        if (empty($naadRepoUrl)) {
            throw new \InvalidArgumentException("Base URL cannot be empty");
        }

        $this->client   = $client;
        $this->baseUrl  = $naadRepoUrl;
    }

    /**
     * Fetches an alert from the NAAD alert repository.
     *
     * @param array $reference - The reference data (sender, id, sent).
     *
     * @return string The alert response body.
     *
     * @throws Exception if an error occurs during the GET request.
     */
    public function fetchAlert(array $reference): string
    {
        $url = $this->constructURL($reference);

        try {
            $response = $this->client->get($url);
            return (string) $response->getBody();
        } catch (RequestException $e) {
            throw new \RuntimeException(
                "Failed to fetch alert: " . $e->getMessage()
            );
        }
    }

    /**
     * Constructs the URL using the provide reference
     *
     * @param array $reference Heartbeat references array parts (sender, id, sent).
     *
     * @return string The constructed URL.
     */
    public function constructURL(array $reference): string
    {
        if (!isset($reference['sent'], $reference['id'])) {
            throw new \InvalidArgumentException(
                "Reference must contain 'sent' and 'id' keys"
            );
        }

        $date          = strtok($reference['sent'], 'T');
        $sanitizedSent = strtr($reference['sent'], self::SANITIZE_RULES);
        $sanitizedId   = strtr($reference['id'], self::SANITIZE_RULES);

        return sprintf(
            self::URL_TEMPLATE,
            $this->baseUrl,
            $date,
            $sanitizedSent,
            $sanitizedId
        );
    }
}
