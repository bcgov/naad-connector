<?php
namespace Bcgov\NaadConnector;
use Bcgov\NaadConnector\NaadVars;
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
    /**
     * Constants used by these methods
     * - Url template for generating an URL string based on inputs
     * - Sanitize rules to translate a reference to an URL-friendly string
     */
    protected const URL_TEMPLATE = 'http://%s/%s/%sI%s.xml';
    protected const SANITIZE_RULES = ['-' => '_', '+' => 'p', ':' => '_'];

    /**
     * Guzzle HTTP client.
     *
     * @var Client
     */
    protected Client $client;

    /**
     * The url of the NAAD alert repository.
     *
     * @var string
     */
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
        $this->client   = $client;
        $this->baseUrl  = $naadRepoUrl;
    }

    /**
     * Fetches an alert from the NAAD alert repository.
     *
     * @param array $reference Heartbeat references array parts (sender, id, sent).
     *
     * @return string The alert response body.
     *
     * @throws Exception if an error occurs during the GET request.
     */
    public function fetchAlert(array $reference): string
    {
        try {
            $url = $this->constructURL($reference);
            $response = $this->client->get($url);

            return (string) $response->getBody();
        } catch (RequestException $e) {
            throw new \Exception("Request failed: " . $e->getMessage());
        }
    }

    /**
     * Builds the URL for the specific set of alerts by date and id
     * that are located in a date-stamped filename in the NAAD alert repository
     * based on the provided reference.
     *
     * @param array $reference Heartbeat references array parts (sender, id, sent).
     *
     * @return string The constructed URL.
     */
    public function constructURL(array $reference): string
    {
        // Extract date and sanitize values for URL construction.
        $date = strtok($reference['sent'], 'T');

        // Build and return the URL.
        return sprintf(
            self::URL_TEMPLATE,
            $this->baseUrl,
            $date,
            strtr($reference['sent'], self::SANITIZE_RULES),
            strtr($reference['id'], self::SANITIZE_RULES),
        );
    }

    /**
     * A debugger method that returns the class instance properties
     * as a string
     *
     * Example use: `error_log(print_r($client->__debugInfo(), true));`
     *
     * @return array    an associative array of defined object accessible
     *                  non-static properties for the specified object in scope.
     *                  If a property have not been assigned a value,
     *                  it will be returned with a null value.
     */
    public function __debugInfo(): array
    {
        return get_object_vars($this);
    }

}
