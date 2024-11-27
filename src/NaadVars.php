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
 *
 * @property-read string $databaseRootPassword The password used to authenticate
 *  to the database which stores Alerts pulled from the NAAD API.
 * @property-read string $databaseHost The name of the database the app uses to
 * store Alerts
 * @property-read string $databasePort The port of the database the app used to
 * store alerts, eg. 3306
 * @property-read string $databaseName The name of the database the app uses to
 * store alerts in.
 * @property-read string $destinationURL The URL of the destination API, eg.
 * local: "https://0.0.0.0/wp-json/naad/v1/alert".
 * @property-read string $destinationUser The username to authenticate the
 * endpoint requests with.
 * @property-read string $destinationPassword The password to authenticate the
 * endpoint requests with.
 * @property-read string $logFilePath The path to the file which will hold the
 * socket connection logs.
 * @property-read string $naadName The instance of NAAD the application is
 * connecting to, eg. NAAD-1, NAAD-2.
 * @property-read string $naadUrl The URL of the NAAD endpoint the application
 * is pulling data from, eg. "streaming1.naad-adna.pelmorex.com".
 * @property-read string $naadRepoUrl The URL of the NAAD Repository, which we
 * fetch missing alerts from, eg. "capcp1.naad-adna.pelmorex.com".
 */
class NaadVars
{
    /**
     * Define the properties for the NaadVars class.
     * these will hold the values of environment variables in local .env files.
     */
    private ?string $databaseRootPassword;
    private ?string $databaseHost;
    private ?string $databasePort;
    private ?string $databaseName;
    private ?string $destinationURL;
    private ?string $destinationUser;
    private ?string $destinationPassword;
    private ?string $logFilePath;
    private ?string $naadName;
    private ?string $naadUrl;
    private ?string $naadRepoUrl;


    /**
     * Environment variable to property mapping.
     */
    private static array $envMap = [
        'databaseRootPassword' => 'MARIADB_ROOT_PASSWORD',
        'databaseHost' => 'MARIADB_SERVICE_HOST',
        'databasePort' => 'MARIADB_SERVICE_PORT',
        'databaseName' => 'MARIADB_DATABASE',
        'destinationURL' => 'DESTINATION_URL',
        'destinationUser' => 'DESTINATION_USER',
        'destinationPassword' => 'DESTINATION_PASSWORD',
        'logFilePath' => 'LOG_FILE_PATH',
        'naadName' => 'NAAD_NAME',
        'naadUrl' => 'NAAD_URL',
        'naadRepoUrl' => 'NAAD_REPO_URL',
    ];

    /**
     *  These default values are used  when the corresponding environment
     * variables are not defined.
     */
    private static array $defaultValues = [
        'logFilePath'  => 'naad-socket.log',
        'naadName'     => 'NAAD-1',
        'naadUrl'      => 'streaming1.naad-adna.pelmorex.com',
        'naadRepoUrl'  => 'capcp1.naad-adna.pelmorex.com'
    ];

    /**
     * This initializes the object's properties by loading values from
     * environment variables. It iterates over the predefined environment
     * variable keys and assigns their corresponding values to the
     * object's properties.
     *
     * @return void
     *
     * @throws Exception If an environment variable is not in .env or defaults.
     */
    public function __construct()
    {
        foreach (self::$envMap as $property => $envKey) {
            // Get the environment variable out of the .env file.
            // If not found there, use a default, if not there, just be null.
            $this->$property = getenv($envKey)
                ?: self::$defaultValues[$property] ?? null;

            // If not found in .env or in defaults, Throw error.
            if ($this->$property === null) {
                throw new \InvalidArgumentException(
                    "Environment variable '$envKey' is required."
                );
            }
        }
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
