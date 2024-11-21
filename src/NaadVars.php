<?php

namespace Bcgov\NaadConnector;

/**
 * Class NaadVars
 *
 * This class is responsible for managing environment variables used in the
 * application. It retrieves values from the environment or uses default values
 * when necessary. The class provides a dynamic way to access these variables
 * through the magic __get and __set methods.
 *
 * Environment variables are essential for configuring the application without
 * hardcoding sensitive information or settings. This class specifically handles
 * database and application-related environment variables.
 *
 * @category Utility
 * @package  Bcgov\NaadVars
 * @author   Richard O'Brien <Richard.Obrien@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 */
class NaadVars
{
    private $rootPassword;
    private $serviceHost;
    private $servicePort;
    private $databaseName;
    private $phpMyAdminPort;
    private $destinationURL;
    private $destinationUser;
    private $destinationPassword;
    private $naadPort;
    private $logFilePath;
    private $naadName;
    private $naadUrl;
    private $naadRepoUrl;


    /**
     * Predefined environment variable keys and their defaults.
     */
    private static array $defaultValues = [
        'logFilePath' => '/var/www/html/naad-socket.log',
        'naadName' => 'NAAD-1',
        'naadUrl' => 'streaming1.naad-adna.pelmorex.com',
        'naadRepoUrl' => 'capcp1.naad-adna.pelmorex.com'
    ];

    /**
     * Constructor for the class.
     *
     * This method initializes the object's properties by loading values from
     * environment variables. It iterates over the predefined environment
     * variable keys and assigns their corresponding values to the
     * object's properties.
     *
     * @throws Exception If an environment variable is not set
     * or cannot be retrieved.
     */
    public function __construct()
    {
        $this->$

        $this -> $rootPassword = get
        $this -> $serviceHost;
        $this -> $servicePort;
        $this -> $databaseName;
        $this -> $phpMyAdminPort;
        $this -> $destinationURL;
        $this -> $destinationUser;
        $this -> $destinationPassword;
        $this -> $naadPort;
        $this -> $logFilePath;
        $this -> $naadName;
        $this -> $naadUrl;
        $this -> $naadRepoUrl;

    }

    public function __get($property): string
    {
        if (property_exists($this, $property)){
            return $this->$property;
        }else {
           throw new \InvalidArgumentException("Property '$property' does not exist.");
        }
    }

}
