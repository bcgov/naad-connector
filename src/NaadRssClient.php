<?php
namespace Bcgov\NaadConnector;

use SimpleXMLElement;

/**
 * NaadSocketClient class fetches from a NAAD RSS feed.
 *
 * @category Client
 * @package  NaadConnector
 * @author   Michael Haswell <Michael.Haswell@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 */
class NaadRssClient
{

    /**
     * The URL of the NAAD RSS feed.
     *
     * @var string
     */
    protected string $address;

    /**
     * The RSS feed XML.
     *
     * @var SimpleXMLElement
     */
    protected SimpleXMLElement $feed;

    /**
     * Constructor for NaadRssClient.
     *
     * @param string $url The URL of the NAAD RSS feed to fetch.
     */
    public function __construct( string $url )
    {
        $this->address = $url;
    }

    /**
     * Fetches an RSS feed XML from the given address.
     *
     * @return int An exit code.
     */
    public function fetch(): int
    {
        // Enables XML error reporting functions (used by libxml_get_errors()).
        $previousUseInternalErrorsValue = libxml_use_internal_errors(true);

        $feed = simplexml_load_file($this->address);
        if (!$feed) {
            print_r('Could not fetch RSS feed.');
            $this->logXmlErrors();
            return 2;
        }

        $feed->rewind();
        if (!$feed->registerXPathNamespace('x', 'http://www.w3.org/2005/Atom')) {
            print_r('Could not register XPath namespace.');
            $this->logXmlErrors();
            return 3;
        }

        $this->feed = $feed;

        // Sets XML error reporting back to its original value.
        libxml_use_internal_errors($previousUseInternalErrorsValue);
        return 1;
    }

    /**
     * Gets an alert from the feed by id.
     *
     * @param string $id The alert id (feed/entry/id in RSS feed, alert/identifier in
     *                   socket response).
     *
     * @return SimpleXMLElement|false SimpleXMLElement of the alert with the given id
     * or false if alert could not be found.
     */
    public function getAlert(string $id): SimpleXMLElement|false
    {
        $entries = $this->feed->xpath(
            sprintf('/x:feed/x:entry[x:id[contains(text(),"%s")]]', $id)
        );
        if (empty($entries)) {
            print_r(sprintf('Alert "%s" not found in RSS feed.', $id));
            return false;
        }
        return $entries[0];
    }

    /**
     * Logs XML errors.
     *
     * @return void
     */
    protected function logXmlErrors()
    {
        foreach (libxml_get_errors() as $error) {
            print_r($error->message);
        }
    }
}
