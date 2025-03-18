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
    public string $secretPath = '/vault/secrets';


    /**
     * Where all the properties are stored.
     *
     * @var array
     */
    protected array $data = [];

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
     * Allows for updating or overriding object parameters.
     *
     * @return void
     */
    abstract protected function configOverrides(): void;
   

    /**
     * Used to initiate the configuration object paramaters.
     *
     * @return void
     */
    public function init(): void
    {
        $this->setVariables();
        $this->configOverrides();
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
     * This gets the final log path based on feedId and the LOG_PATH env.
     *
     * @param string $path   The path which comes from the env LOG_PATH.
     * @param string $feedId The feed identifier, which is used to determine
     *                       naad-1, naad-2, migration, or cleanup.
     *
     * @return string
     */
    protected function getLogPath(string $path, string $feedId ): string
    {
        // Remove any existing config that might use a log file.
        $path = rtrim($path, '.log');
        return sprintf("%s/naad-%s/app.log", rtrim($path, '/'), $feedId);
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
     * @return void
     *
     * @throws \InvalidArgumentException If the property does not exist.
     */
    public function __get(string $name)
    {
        if (array_key_exists($name, $this->data) && !empty($this->data[$name])) {
            return $this->data[$name];
        }
        
        $this->throwError($name);
    }



    /**
     * Magic Setter class for properties, which does simple sanitization.
     *
     * @param string     $name  The name of the property you want to set.
     * @param string|int $value The value of the property to be set.
     *
     * @return void
     */
    public function __set(string $name, $value): void
    {
        if (empty($value)) {
            $this->throwError($name);
        }
        if (is_string($value)) {
            $this->data[$name] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        } else {
            $this->data[$name] = $value;
        }
        
    }

    /**
     * This allows to set the secrets path location when used with vault secrets.
     *
     * @param string $path The secret's path folder for use with vault secrets.
     *
     * @return void
     */
    public function setSecretPath( string $path ): void
    {
        $this->secretPath = $path;
    }

    /**
     * Helper function to have consistent exception for set/get
     * invalid property value.
     *
     * @param string $name The property that is throwing exception.
     *
     * @return void
     */
    private function throwError(string $name )
    {
        throw new \InvalidArgumentException(
            "Property '$name' does not exist."
        );
    }
}