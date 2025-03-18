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
 * @property string $destinationURL The URL of the destination API, eg.
 * local: "https://0.0.0.0/wp-json/naad/v1/alert".
 * @property string $destinationUser The username to authenticate the
 * endpoint requests with.
 * @property string $destinationPassword The password to authenticate the
 * endpoint requests with.
 * @property string $naadUrl The URL of the NAAD endpoint the application
 * is pulling data from, eg. "streaming1.naad-adna.pelmorex.com".
 * @property string $naadRepoUrl The URL of the NAAD Repository, which we
 * fetch missing alerts from, eg. "capcp1.naad-adna.pelmorex.com".
 *
 * @inheritDoc
 */
class ApplicationConfig extends DatabaseConfig
{

    /**
     * Allows for updating or overriding object parameters.
     *
     * @return void
     */
    protected function configOverrides(): void
    {
        if (intval($this->feedId) > 1) {
            $this->naadUrl = 'streaming2.naad-adna.pelmorex.com';
            $this->naadRepoUrl = 'capcp2.naad-adna.pelmorex.com';
        }
        parent::configOverrides();
    }

    /**
     * Where the default values are stored as the ENV variable.
     *
     * @return array
     */
    protected function getDefaults(): array
    {
        return array_merge(
            parent::getDefaults(),
            [
                'FEED_ID' => '1',
                'NAAD_URL' => 'streaming1.naad-adna.pelmorex.com',
                'NAAD_REPO_URL' => 'capcp1.naad-adna.pelmorex.com',
                
            ]
        );
    }

    /**
     * The mapping from ENV variable to object property.
     *
     * @return array
     */
    protected  function getEnvMap(): array
    {
        return array_merge(
            parent::getEnvMap(),
            [
                'destinationURL' => 'DESTINATION_URL',
                'destinationUser' => 'DESTINATION_USER',
                'destinationPassword' => 'DESTINATION_PASSWORD',
                'naadUrl' => 'NAAD_URL',
                'naadRepoUrl' => 'NAAD_REPO_URL',
                
            ]
        );
        
    }
}