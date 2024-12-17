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
     * @param Client|null   $client   The Guzzle HTTP client to use.
     *                                Defaults to a new Client instance.
     * @param NaadVars|null $naadVars The NaadVars instance to use.
     *                                Defaults to a new NaadVars instance.
     */
    public function __construct(
        Client $client     = null,
        NaadVars $naadVars = null
    ) {
        $this->client   = $client ?: new Client();
        $naadVars       = $naadVars ?: new NaadVars();
        $this->baseUrl  = $naadVars->naadRepoUrl;
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
        $sanitize = fn(string $value): string => strtr(
            $value,
            self::SANITIZE_RULES
        );

        // Build and return the URL.
        return sprintf(
            self::URL_TEMPLATE,
            $this->baseUrl,
            $date,
            $sanitize($reference['sent']),
            $sanitize($reference['id'])
        );
    }

    /**
     * Magic getter method to retrieve the value of a property.
     *
     * It checks if the requested property exists in the object.
     * If it does, it returns the value of that property;
     * otherwise, it throws an InvalidArgumentException.
     *
     * @param string $property The name of the property to retrieve.
     *
     * @return string The value of the requested property.
     *
     * @throws \InvalidArgumentException If the property does not exist.
     */
    public function __get($property): string
    {
        if (!property_exists($this, $property)) {
            throw new \InvalidArgumentException(
                "Property '$property' does not exist."
            );
        }

        return $this->$property;
    }
}
