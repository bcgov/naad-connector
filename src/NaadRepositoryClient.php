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
     * Constructor for NaadRepositoryClient.
     */
    public function __construct()
    {
        $naadVars       = new NaadVars();
        $this->baseUrl  = $naadVars->naadRepoUrl;
    }

    /**
     * Fetches an alert from the NAAD alert repository.
     *
     * @param array $reference Heartbeat references array parts (sender, id, sent).
     *
     * @return string|null The alert response body or null if an error occurs.
     *
     * @throws Exception if an error occurs during the GET request.
     */
    public function fetchAlert(array $reference): ?string
    {
        $url = $this->getURL($reference);
        $this->client = new Client();

        try {
            $response = $this->client->get($url);

            return (string) $response->getBody();
        } catch (RequestException $e) {
            error_log("Request failed: " . $e->getMessage());
            return null;
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
    public function getURL(array $reference)
    {
        // Extract and sanitize values from the reference array.
        $date = strtok($reference['sent'], 'T');
        $sent = $this->replaceUrlCharacters($reference['sent']);
        $id = $this->replaceUrlCharacters($reference['id']);

        // Build the URL.
        return sprintf(
            'http://%s/%s/%sI%s.xml',
            $this->baseUrl,
            $date,
            $sent,
            $id
        );
    }

    /**
     * Replaces specific characters in a string as per NAAD URL requirements.
     *
     * @param string $s The input string.
     *
     * @return string The modified string.
     */
    protected function replaceUrlCharacters(string $s): string
    {
        return strtr($s, ['-' => '_', '+' => 'p', ':' => '_']);
    }

}
