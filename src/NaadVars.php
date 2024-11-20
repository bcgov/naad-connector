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
    protected array $envVars = [
        // Database environment variables
        'rootPassword' => 'MARIADB_ROOT_PASSWORD',
        'serviceHost' => 'MARIADB_SERVICE_HOST',
        'servicePort' => 'MARIADB_SERVICE_PORT',
        'databaseName' => 'MARIADB_DATABASE',
        'phpMyAdminPort' => 'PHPMYADMIN_PORT',

        // Application environment variables
        'destinationURL' => 'DESTINATION_URL',
        'destinationUser' => 'DESTINATION_USER',
        'destinationPassword' => 'DESTINATION_PASSWORD',
        'naadPort' => 'NAAD_PORT',
        'logFilePath' => 'LOG_FILE_PATH',
        'naadName' => 'NAAD_NAME',
        'naadUrl' => 'NAAD_URL',
    ];

    protected array $values = [];

    /**
     * Predefined environment variable keys and their defaults.
     */
    protected static array $defaultValues = [
        'LOG_FILE_PATH' => '/var/www/html/naad-socket.log',
        'NAAD_NAME' => 'NAAD-1',
        'NAAD_URL' => 'streaming1.naad-adna.pelmorex.com',
        'NAAD_REPO_URL' => 'capcp1.naad-adna.pelmorex.com'
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
        foreach ($this->envVars as $property => $envKey) {
            $this->values[$property] = $this->_getEnv($envKey);
        }
    }

    /**
     * Retrieves the value of an environment variable by its key.
     *
     * This method first checks if the key exists in the predefined default values.
     * If it does, the corresponding default value is returned. If not, it attempts
     * to retrieve the value from the environment variables. If the variable is not
     * set, a RuntimeException is thrown with instructions on how
     * to resolve the issue.
     *
     * @param string $key The key of the environment variable to retrieve.
     *
     * @return string The value of the environment variable.
     *
     * @throws \RuntimeException If the environment variable is not set.
     */
    private function _getEnv(string $key): string
    {

        if (array_key_exists($key, self::$defaultValues)) {
            return self::$defaultValues[$key];
        }

        $value = getenv($key);
        if ($value === false) {

            throw new \RuntimeException(
                sprintf(
                    "\nEnvironment variable '%s' is not set. Please ensure:\n" .
                        "1. The .env file contains this key-value pair.\n" .
                        "2. The Docker container has the variable set.\n",
                    $key
                )
            );
        }

        return $value;
    }

    /**
     * Magic method to retrieve the value of a property.
     *
     * This method allows access to properties defined in the
     * $values array. If the requested property does not exist,
     * an InvalidArgumentException is thrown.
     *
     * @param string $name The name of the property to retrieve.
     *
     * @return string The value of the requested property.
     *
     * @throws \InvalidArgumentException If the property does not exist in the
     * $values array.
     */
    public function __get(string $name): string
    {
        if (!array_key_exists($name, $this->values)) {
            throw new \InvalidArgumentException("Property '$name' does not exist.");
        }

        return $this->values[$name];
    }

    ///////////// STATIC METHODS AND PROPS /////////////////

    /**
     * Predefined environment variable keys and their defaults.
     */
    // protected static array $defaultValues = [
    //     'MARIADB_ROOT_PASSWORD' => 'cm9vdHBhc3N3b3Jk',
    //     'MARIADB_SERVICE_HOST' => 'mariadb',
    //     'MARIADB_SERVICE_PORT' => '3306',
    //     'MARIADB_DATABASE' => 'naad_connector',
    //     'PHPMYADMIN_PORT' => '8082',
    //     'DESTINATION_URL' => 'https://0.0.0.0/wp-json/naad/v1/alert',
    //     'DESTINATION_USER' => 'naadbot',
    //     'DESTINATION_PASSWORD' => 'AAAA AAAA AAAA AAAA',
    //     'NAAD_PORT' => '8080',
    //     'LOG_FILE_PATH' => '/var/www/html/naad-socket.log',
    //     'NAAD_NAME' => 'NAAD-1',
    //     'NAAD_URL' => 'streaming1.naad-adna.pelmorex.com',
    // ];
    /**
     * Retrieve an environment variable with an optional default value.
     *
     * @param string $key     The name of the environment variable.
     * @param string|null $default Optional default value if the variable is not set.
     *
     * @return string The value of the environment variable or the default.
     *
     * @throws \RuntimeException If the variable is not set and
     * no default is provided.
     */
    // public static function getEnv(string $key, ?string $default = null): string {
    //     $value = getenv($key);

    //     if ($value !== false) {
    //         return $value;
    //     }

    //     if ($default !== null) {
    //         return $default;
    //     }

    //     if (array_key_exists($key, self::$defaultValues)) {
    //         return self::$defaultValues[$key];
    //     }

    //     throw new \RuntimeException(
    //         sprintf(
    //             "Environment variable '%s' is not set, and no default value is provided.",
    //             $key
    //         )
    //     );
    // }

    /**
     * Retrieve a preconfigured environment variable by property name.
     *
     * @param string $property The predefined environment variable name.
     *
     * @return string The value of the environment variable or the default.
     *
     * @throws \InvalidArgumentException If the property is not predefined.
     */
    // public static function getProperty(string $property): string {
    //     $envKey = array_search($property, array_flip(self::$defaultValues), true);

    //     if ($envKey === false) {
    //         throw new \InvalidArgumentException(
            //  "Property '$property' is not defined."
    // .       );
    //     }

    //     return self::getEnv($envKey, self::$defaultValues[$envKey] ?? null);
    // }
}
