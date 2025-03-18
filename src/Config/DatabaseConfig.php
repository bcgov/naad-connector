<?php

namespace Bcgov\NaadConnector\Config;
use Bcgov\NaadConnector\Config\BaseConfig;

/**
 * Class BaseConfig
 *
 * @category Utility
 * @package  Bcgov\NaadConnector\Config\BaseConfig
 * @author   Digital Engagement Solutions <govwordpress@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 *
 * @property-read string $logLevel The minimum level of logs the Logger will send.
 * @property-read int $alertsToKeep The number of alerts to keep in the database.
 * @property-read string $logPath The path to the log file to write to.
 * @property-read int    $logRetentionDays The number of days to keep a log file
 * before rotating.
 *
 * @inheritDoc
 */
class DatabaseConfig extends BaseConfig
{
    /**
     * Allows for updating or overriding object parameters.
     *
     * @return void
     */
    protected function configOverrides(): void
    {
        $this->logPath = $this->getLogPath($this->logPath, $this->feedId);
    }

    /**
     * Where the default values are stored as the ENV variable.
     *
     * @return array
     */
    protected function getDefaults(): array
    {
        return [
            'FEED_ID' => 'database',
            'LOG_LEVEL'      => 'info',
            'ALERTS_TO_KEEP' => 100,
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
            'databaseRootPassword' => 'MARIADB_ROOT_PASSWORD',
            'databaseHost' => 'MARIADB_SERVICE_HOST',
            'databasePort' => 'MARIADB_SERVICE_PORT',
            'databaseName' => 'MARIADB_DATABASE',
            'logLevel' => 'LOG_LEVEL',
            'logPath' => 'LOG_PATH',
            'logRetentionDays' => 'LOG_RETENTION_DAYS',
            'alertsToKeep' => 'ALERTS_TO_KEEP',
            'feedId' => 'FEED_ID'
        ];
    }
   
}