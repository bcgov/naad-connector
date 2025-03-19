<?php

namespace Bcgov\NaadConnector\Config;
use Bcgov\NaadConnector\Config\BaseConfig;

/**
 * Class LoggerConfig
 *
 * @category Utility
 * @package  Bcgov\NaadConnector\Config\BaseConfig
 * @author   Digital Engagement Solutions <govwordpress@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 *
 * @inheritDoc
 */
class LoggerConfig extends BaseConfig
{
    /**
     * The minimum level of logs the Logger will send.
     *
     * @var string
     */
    private string $logLevel;

    /**
     * The path to the log file to write to.
     *
     * @var string
     */
    private string $logPath;

    /**
     * The number of days to keep a log file before rotating.
     *
     * @var int
     */
    private int $logRetentionDays;

    /**
     * Log subpath where logs will be stored.
     *
     * @var string
     */
    private string $logSubPath;


    /**
     * Constructor.
     *
     * @param string $logSubPath Allows to configure log path.
     * @param string $secretPath need ability to change secret path.
     */
    public function __construct(
        string $logSubPath="socket",
        string $secretPath="/vault/secrets"
    ) {
        $this->logSubPath = $logSubPath;
        parent::__construct($secretPath);
    }

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
            'LOG_LEVEL'      => 'info',
            'LOG_RETENTION_DAYS' => 0, // No rotation.
            'LOG_PATH' => '/logs',
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
            'logLevel' => 'LOG_LEVEL',
            'logPath' => 'LOG_PATH',
            'logRetentionDays' => 'LOG_RETENTION_DAYS',
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
        } elseif ($name === 'logPath') {
            // Remove any existing config that might use a log file.
            $path = rtrim($value, '.log');
            $this->logPath =  sprintf(
                "%s/%s/app.log",
                rtrim($path, '/'),
                $this->logSubPath
            );
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