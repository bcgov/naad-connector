<?php

namespace Bcgov\NaadConnector\Config ;
use Bcgov\NaadConnector\Config\BaseConfig;

/**
 * Class ApplicationConfig
 *
 * @category Utility
 * @package  Bcgov\NaadConnector\Config\BaseConfig
 * @author   Digital Engagement Solutions <govwordpress@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 *
 * @inheritDoc
 */
class ApplicationConfig extends BaseConfig
{
    /**
     * The Feed id to determine if it is socket-1 or socket-2.
     *
     * @var integer
     */
    private int $feedId;
    /**
     * The URL of the NAAD endpoint the application
     * is pulling data from, eg. "streaming1.naad-adna.pelmorex.com".
     *
     * @var string
     */
    private string $naadUrl;
    /**
     * The URL of the NAAD Repository, which we
     * fetch missing alerts from, eg. "capcp1.naad-adna.pelmorex.com".
     *
     * @var string
     */
    private string $naadRepoUrl;
    /**
     * The URL of the destination API, eg.
     * local: "https://0.0.0.0/wp-json/naad/v1/alert".
     *
     * @var string
     */
    private string $destinationUrl;
    /**
     * The username to authenticate the endpoint requests with.
     *
     * @var string
     */
    private string $destinationUser;
    /**
     * The password to authenticate the endpoint requests with.
     *
     * @var string
     */
    private string $destinationPassword;

    /**
     * An abstract function that assigns all the properties from ENV variables.
     *
     * @return void
     */
    protected function assignProperties(): void
    {
        $this->feedId = $this->getPropertyValueFromEnv('FEED_ID', 1);
        $this->destinationUrl = $this->getPropertyValueFromEnv('DESTINATION_URL');
        $this->destinationUser = $this->getPropertyValueFromEnv('DESTINATION_USER');
        $this->destinationPassword
            = $this->getPropertyValueFromEnv('DESTINATION_PASSWORD');
        if (intval($this->feedId) > 1) {
            $this->naadUrl = 'streaming2.naad-adna.pelmorex.com';
            $this->naadRepoUrl = 'capcp2.naad-adna.pelmorex.com';
        } else {
            $this->naadUrl = $this->getPropertyValueFromEnv(
                'NAAD_URL',
                'streaming1.naad-adna.pelmorex.com'
            );
            $this->naadRepoUrl = $this->getPropertyValueFromEnv(
                'NAAD_REPO_URL',
                'capcp1.naad-adna.pelmorex.com'
            );
        }
    }
    

    /**
     * Get the FeedId.
     *
     * @return int
     */
    public function getFeedId(): int
    {
        return $this->feedId;
    }

    /**
     * Get the destinationURL.
     *
     * @return string
     */
    public function getDestinationUrl(): string
    {
        return $this->destinationUrl;
    }

    /**
     * Get the destinationUser.
     *
     * @return string
     */
    public function getDestinationUser(): string
    {
        return $this->destinationUser;
    }

    /**
     * Get the destinationPassword.
     *
     * @return string
     */
    public function getDestinationPassword(): string
    {
        return $this->destinationPassword;
    }

    /**
     * Get the naadUrl.
     *
     * @return string
     */
    public function getNaadUrl(): string
    {
        return $this->naadUrl;
    }

    /**
     * Get the naadRepoUrl.
     *
     * @return string
     */
    public function getNaadRepoUrl(): string
    {
        return $this->naadRepoUrl;
    }
}