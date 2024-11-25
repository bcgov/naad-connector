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

    private ?string $rootPassword;
    private ?string $serviceHost;
    private ?string $servicePort;
    private ?string $databaseName;
    private ?string $phpMyAdminPort;
    private ?string $destinationURL;
    private ?string $destinationUser;
    private ?string $destinationPassword;
    private ?string $logFilePath;
    private ?string $naadName;
    private ?string $naadUrl;
    private ?string $naadRepoUrl;


    /**
     * Predefined environment variable keys and their defaults.
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
     * @throws Exception If an environment variable is not set
     * or cannot be retrieved.
     */
    public function __construct()
    {

        $envMap = [
            // Destination client.
            'destinationURL' => 'DESTINATION_URL',
            'destinationUser' => 'DESTINATION_USER',
            'destinationPassword' => 'DESTINATION_PASSWORD',
            // Socket logger.
            'logFilePath' => 'LOG_FILE_PATH',

            // Naad socket connection
            'naadName' => 'NAAD_NAME',
            'naadUrl' => 'NAAD_URL',

            'rootPassword' => 'MARIADB_ROOT_PASSWORD',
            'serviceHost' => 'MARIADB_SERVICE_HOST',
            'servicePort' => 'MARIADB_SERVICE_PORT',
            'databaseName' => 'MARIADB_DATABASE',
            'phpMyAdminPort' => 'PHPMYADMIN_PORT',
            'naadRepoUrl' => 'NAAD_REPO_URL',
        ];

        $this->initializeProperties($envMap);
    }

    /**
     * Initializes class props over a map of key/value pairs.
     * It assigns values to the properties either from default values
     * or from the environment variables.
     *
     * @param array $envMap An associative array where keys are property names
     *                      and values are corresponding environment variable keys.
     *
     * @return void This function does not return a value.
     */
    private function initializeProperties(array $envMap): void
    {
        foreach ($envMap as $property => $envKey) {
            // Assign defaults first.
            if (array_key_exists($property, self::$defaultValues)) {
                $this->$property = $this::$defaultValues[$property];
            } else {
                $this->$property = getenv($envKey);
            }
        }
    }

    /**
     * Magic setter method for setting the value of a property.
     *
     * @param string $property The name of the property to set.
     * @param mixed  $value    The value to assign to the property.
     *
     * @return void
     */
    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
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
        // Check if the property exists in the object
        if (!property_exists($this, $property)) {
            // Throw an exception if the property does not exist
            throw new \InvalidArgumentException(
                "Property '$property' does not exist."
            );
        }
        // Return the value of the requested property
        return $this->$property;
    }

    /**
     * Converts the object's properties to an array.
     *
     * This is for debugging purposes.
     *
     * @return array An array containing the object's properties.
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
