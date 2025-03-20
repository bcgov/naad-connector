<?php

namespace Bcgov\NaadConnector\Config;

/**
 * Class BaseConfig
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
 * @package  Bcgov\NaadConnector\Config\BaseConfig
 * @author   Digital Engagement Solutions <govwordpress@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 */
abstract class BaseConfig
{

    /**
     * Can use files the same name as the ENV,
     * the path is where to find the files
     *
     * @example /vault/secrets/MY_PASSWORD, same as env as MY_PASSWORD.
     *
     * @var string
     */
    private string $secretPath;

    /**
     * The ENV data.
     *
     * @var array
     */
    private array $envData = [];


    /**
     * Assigns all the ENV values to the class properties.
     *
     * @return void
     */
    abstract protected function assignProperties(): void;

    /**
     * Used to initiate the configuration object paramaters.
     *
     * @param string $secretPath A way of changing the secrets
     *                           path if using files for env.
     */
    public function __construct(string $secretPath='/vault/secrets')
    {
        $this->secretPath = rtrim($secretPath, '/');
        $this->envData = getenv();
        // Sets all the values
        $this->assignProperties();

    }

    /**
     * Returns the ENV variable value, and validates.
     *
     * @param string          $envKey  The ENV key.
     * @param string|int|null $default the string or integer or null default.
     *
     * @return mixed
     */
    protected function getPropertyValueFromEnv( string $envKey, $default=null )
    {
        $value = null;
        $secretFileName = sprintf("%s/%s", $this->secretPath, $envKey);
        // This means a secret file exists, to get the contents.
        if (file_exists($secretFileName) ) {
            $value = rtrim(file_get_contents($secretFileName), "\r\n");
        } else {
            $value = array_key_exists($envKey, $this->envData) ?
                    $this->envData[$envKey]:
                    $default;
        }
        if (empty($value)) {
            // @codeCoverageIgnoreStart
            throw new \InvalidArgumentException(
                "Property '$envKey' does not exist."
            );
            // @codeCoverageIgnoreEnd
        }
        return $value;
    }
}
