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
     * An abstract function that assigns all the properties from ENV variables.
     *
     * @return void
     */
    protected function assignProperties(): void
    {
        $this->logLevel = $this->getPropertyValueFromEnv('LOG_LEVEL', 'info');
        $this->logRetentionDays = $this->getPropertyValueFromEnv(
            'LOG_RETENTION_DAYS', 
            0
        );
        $this->logPath = $this->setLogPath();
       
    }

    /**
     * Sets log path based on subpath, and does some validation.
     *
     * @return string
     */
    private function setLogPath(): string
    {
        $path = $this->getPropertyValueFromEnv('LOG_PATH', '/logs');
        $path = rtrim($path, '.log');
        $path = sprintf(
            "%s/%s/app.log",
            rtrim($path, '/'),
            $this->logSubPath
        );
        return $path;
    }

    /**
     * Get the logLevel.
     *
     * @return string
     */
    public function getLogLevel(): string
    {
        return $this->logLevel;
    }

    /**
     * Get the logRetentionDays.
     *
     * @return int
     */
    public function getLogRetentionDays(): int
    {
        return $this->logRetentionDays;
    }

    /**
     * Get the FeedId.
     *
     * @return string
     */
    public function getLogPath(): string
    {
        return $this->logPath;
    }
   
}