<?php

namespace Bcgov\NaadConnector\Config;
use Bcgov\NaadConnector\Config\BaseConfig;

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
class DatabaseConfig extends BaseConfig
{
    /**
     * The password used to authenticate
     * to the database which stores Alerts pulled from the NAAD API.
     *
     * @var string
     */
    private string $databaseRootPassword;
    /**
     * The hostname of the database
     *
     * @var string
     */
    private string $databaseHost;
    /**
     * The Host port of the database
     *
     * @var integer
     */
    private int $databasePort;
    /**
     * The Name of the database.
     *
     * @var string
     */
    private string $databaseName;

    /**
     * The number of alerts to keep in the database.
     *
     * @var integer
     */
    private int $alertsToKeep;

    /**
     * Allows for updating or overriding object parameters.
     *
     * @return void
     */
    protected function afterSetupHook(): void
    {
    
    }

    /**
     * Where the default values are stored as the ENV variable.
     *
     * @return array
     */
    protected function getDefaults(): array
    {
        return [
            'ALERTS_TO_KEEP' => 100,
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
            'databaseRootPassword' => 'MARIADB_ROOT_PASSWORD',
            'databaseHost' => 'MARIADB_SERVICE_HOST',
            'databasePort' => 'MARIADB_SERVICE_PORT',
            'databaseName' => 'MARIADB_DATABASE',
            'alertsToKeep' => 'ALERTS_TO_KEEP',
        ];
    }

    /**
     * The Setter override to set parameters.
     *
     * @param string $name  The name of the property.
     * @param mixed  $value The value to set to the property.
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