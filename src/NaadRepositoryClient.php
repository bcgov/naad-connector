<?php
namespace Bcgov\NaadConnector;
use Bcgov\NaadConnector\NaadVars;

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
     * Per NAAD documentation, certain characters must be replaced when
     * building the URL for the short-term alert repository.
     *
     * @param string $s The string to perform the replacement on.
     *
     * @return string
     */
    protected function replaceUrlCharacters( string $s ): string
    {
        return str_replace(
            [ '-', '+', ':' ],
            [ '_', 'p', '_' ],
            $s
        );
    }

    /**
     * Fetches an alert from the NAAD alert repository.
     *
     * @param array $reference Heartbeat references array parts (sender, id, sent).
     *
     * @return string
     */
    public function fetchAlert(
        array $reference,
    ): string {
        $url = sprintf(
            'http://%s/%s/%sI%s.xml',
            $this->baseUrl,
            explode('T', $reference['sent'])[0],
            $this->replaceUrlCharacters($reference['sent']),
            $this->replaceUrlCharacters($reference['id']),
        );

        // Open curl connection.
        $curl = curl_init();

        // Set repository url.
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        // GET from repository.
        $result = curl_exec($curl);
        print_r(curl_error($curl));

        // Close curl connection.
        curl_close($curl);

        return $result;
    }
}
