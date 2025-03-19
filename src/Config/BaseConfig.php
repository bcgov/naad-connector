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
     * Where the default values are stored as the ENV variable.
     *
     * @return array
     */
    abstract protected function getDefaults(): array;

    /**
     * The mapping from ENV variable to object property.
     *
     * @return array
     */
    abstract protected function getEnvMap(): array;

    /**
     * Hook for after setup of the variable config.
     *
     * @return void
     */
    abstract protected function afterSetupHook(): void;


    /**
     * Used to initiate the configuration object paramaters.
     *
     * @param string $secretPath A way of changing the secrets
     *                           path if using files for env.
     */
    public function __construct(string $secretPath='/vault/secrets')
    {
        $this->secretPath = rtrim($secretPath, '/');
        $this->setVariables();
        $this->afterSetupHook();
    }

    /**
     * This sets the object parameter variables from the ENV global variables.
     *
     * @return void
     */
    private function setVariables(): void
    {
        $env = $this->getEnv();
        foreach ($this->getEnvMap() as $property => $envKey) {
            $secretFileName = sprintf("%s/%s", $this->secretPath, $envKey);
            // This means a secret file exists, to get the contents.
            if (file_exists($secretFileName) ) {
                $this->$property = rtrim(file_get_contents($secretFileName), "\r\n");
            } else {
                $this->$property = array_key_exists($envKey, $env) ?
                    $env[$envKey]:
                    null;
            }
        }
    }

    /**
     * Loads the default values with global ENV variables.
     *
     * @return array
     */
    public function getEnv(): array
    {
        return array_merge($this->getDefaults(), getenv());
    }


    /**
     * Magic getter method to retrieve the value of a property.
     *
     * It checks if the requested property exists in the object.
     * If it does, it returns the value of that property;
     * otherwise, it throws an InvalidArgumentException.
     *
     * @param string $name The name of the property to retrieve.
     *
     * @codeCoverageIgnore
     *
     * @return void
     *
     * @throws \InvalidArgumentException If the property does not exist.
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name) ) {
            return $this->$name;
        }
        $this->throwError($name);
    }

    /**
     * Helper function to have consistent exception for set/get
     * invalid property value.
     *
     * @param string $name The property that is throwing exception.
     *
     * @return void
     */
    protected function throwError(string $name )
    {
        throw new \InvalidArgumentException(
            "Property '$name' does not exist."
        );
    }

    /**
     * Helper function to get all the config values.
     *
     * @codeCoverageIgnore
     *
     * @return array
     */
    public function getConfigValues(): array
    {
        $values = [];
        foreach ( array_keys($this->getEnvMap()) as $keys) {
            $values[$keys] = $this->$keys;
        }
        $values['secretPath'] = $this->secretPath;
        return $values;
    }
}