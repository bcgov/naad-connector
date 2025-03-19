<?php

namespace Bcgov\NaadConnector\Config ;
use Bcgov\NaadConnector\Config\DatabaseConfig;

/**
 * Class BaseConfig
 *
 * @category Utility
 * @package  Bcgov\NaadConnector\Config\BaseConfig
 * @author   Digital Engagement Solutions <govwordpress@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 *
 * @inheritDoc
 */
class ApplicationConfig extends DatabaseConfig
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
    private string $destinationURL;
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
     * Allows for updating or overriding object parameters.
     *
     * @return void
     */
    protected function afterSetupHook(): void
    {
        if (intval($this->feedId) > 1) {
            $this->naadUrl = 'streaming2.naad-adna.pelmorex.com';
            $this->naadRepoUrl = 'capcp2.naad-adna.pelmorex.com';
        }

    }

    /**
     * Where the default values are stored as the ENV variable.
     *
     * @return array
     */
    protected function getDefaults(): array
    {
        return [
                'FEED_ID' => 1,
                'NAAD_URL' => 'streaming1.naad-adna.pelmorex.com',
                'NAAD_REPO_URL' => 'capcp1.naad-adna.pelmorex.com',
        ];
    }

    /**
     * The mapping from ENV variable to object property.
     *
     * @return array
     */
    protected  function getEnvMap(): array
    {
        return [
                'destinationURL' => 'DESTINATION_URL',
                'destinationUser' => 'DESTINATION_USER',
                'destinationPassword' => 'DESTINATION_PASSWORD',
                'naadUrl' => 'NAAD_URL',
                'naadRepoUrl' => 'NAAD_REPO_URL',
                'feedId' => 'FEED_ID',
        ];
    }

    /**
     * The Setter override to set parameters.
     *
     * @param string $name  The name of the property.
     * @param mixed  $value The value to set to the property.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function __set(string $name, $value )
    {
        if (empty($value)) {
            parent::throwError($name);
        } else {
            $this->$name = $value;
        }
    }
   
    /**
     * The getter class override to get properties.
     *
     * @param string $name the property name to get.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name) ) {
            return $this->$name;
        }
        parent::throwError($name);
    }
}