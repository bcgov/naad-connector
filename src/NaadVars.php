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

    /**
     * Define the properties for the NaadVars class.
     * these will hold the values of environment variables in local .env files.
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
     * @property-read string $naadName The instance of NAAD the application is
     * connecting to, eg. NAAD-1, NAAD-2.
     * @property-read string $naadUrl The URL of the NAAD endpoint the application
     * is pulling data from, eg. "streaming1.naad-adna.pelmorex.com".
     * @property-read string $naadRepoUrl The URL of the NAAD Repository, which we
     * fetch missing alerts from, eg. "capcp1.naad-adna.pelmorex.com".
     * @property-read string $logLevel The minimum level the Logger will send.
     * @property-read int $alertsToKeep The number of alerts to keep in the database.
     * @property-read string $logPath The path to the log file to write to.
     * @property-read int    $logRetentionDays The number of days to keep a log file
     * before rotating.
     * @var           array
     */
    private array $data = [];

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
     * This initializes the object's properties by loading values from environment 
     * variables or from file, indicated by the $secretPath and filename identical to
     * the environment variable. It iterates over the predefined environment variable
     * keys and assigns their corresponding values to the object's properties.
     *
     * @param $secretPath The secrets path for allowing secrets to be stored in  
     *                    files as the same name as the env variable.
     * 
     * @return void
     */
    public function __construct(string $secretPath="/vault/secrets")
    {
        $this->secretPath = $secretPath;
        $this->setVariables();
        $this->setDeploymentVariations();
    }

    /**
     * This is how ENV variables get transformed to class properties.
     *
     * @return void
     */
    private function setVariables()
    {
        $env = $this->getEnvs();
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
     * Different deployments, require different configurations, this handles
     * these configuration differences.
     *
     * @return void
     */
    private function setDeploymentVariations(): void
    {
        if (intval($this->feedId) > 1) {
            $this->naadUrl = 'streaming2.naad-adna.pelmorex.com';
            $this->naadRepoUrl = 'capcp2.naad-adna.pelmorex.com';
        }
        $this->logPath = $this->getLogPath($this->logPath, $this->feedId);
    }


    /**
     * Gets the system ENV variables, with pre-defined defaults.
     *
     * @return array
     */
    private function getEnvs(): array
    { 
        $defaults = [
            'FEED_ID' => 'default',
            'LOG_LEVEL'      => 'info',
            'ALERTS_TO_KEEP' => 100,
            'LOG_RETENTION_DAYS' => 0, // No rotation.
            'NAAD_URL' => 'streaming1.naad-adna.pelmorex.com',
            'NAAD_REPO_URL' => 'capcp1.naad-adna.pelmorex.com',
            'LOG_PATH' => '/logs',
        ];
        return array_merge($defaults, getenv());     
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
    private function getLogPath(string $path, string $feedId ): string
    {
        // Remove any existing config that might use a log file.
        $path = rtrim($path, '.log');
        return sprintf("%s/naad-%s/app.log", rtrim($path, '/'), $feedId);
    }

    /**
     * This returns the mapping used for the NaadVars from the ENV.
     *
     * @return array
     */
    private function getEnvMap(): array
    {
        return [
            'databaseRootPassword' => 'MARIADB_ROOT_PASSWORD',
            'databaseHost' => 'MARIADB_SERVICE_HOST',
            'databasePort' => 'MARIADB_SERVICE_PORT',
            'databaseName' => 'MARIADB_DATABASE',
            'destinationURL' => 'DESTINATION_URL',
            'destinationUser' => 'DESTINATION_USER',
            'destinationPassword' => 'DESTINATION_PASSWORD',
            'naadUrl' => 'NAAD_URL',
            'naadRepoUrl' => 'NAAD_REPO_URL',
            'logLevel' => 'LOG_LEVEL',
            'alertsToKeep' => 'ALERTS_TO_KEEP',
            'logPath' => 'LOG_PATH',
            'logRetentionDays' => 'LOG_RETENTION_DAYS',
            'feedId' => 'FEED_ID'
        ];
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
        
        throw new \InvalidArgumentException(
            "Property '$name' does not exist."
        );
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
        if (is_string($value)) {
            $this->data[$name] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        } else {
            $this->data[$name] = $value;
        }
        
    }
}