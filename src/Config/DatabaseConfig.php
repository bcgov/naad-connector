<?php

namespace Bcgov\NaadConnector\Config;
use Bcgov\NaadConnector\Config\BaseConfig;

/**
 * Class DatabaseConfig
 *
 * @category Utility
 * @package  Bcgov\NaadConnector\Config\DatabaseConfig
 * @author   Digital Engagement Solutions <govwordpress@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 *
 * @inheritDoc
 */
class DatabaseConfig extends BaseConfig
{
    /**
     * The username used to authenticate
     * to the database which stores Alerts pulled from the NAAD API.
     *
     * @var string
     */
    private string $databaseServiceUser;
    /**
     * The password used to authenticate
     * to the database which stores Alerts pulled from the NAAD API.
     *
     * @var string
     */
    private string $databaseServicePassword;
    /**
     * The hostname of the database
     *
     * @var string
     */
    private string $databaseHost;
    /**
     * The Host port of the database
     *
     * @var integer
     */
    private int $databasePort;
    /**
     * The Name of the database.
     *
     * @var string
     */
    private string $databaseName;

    /**
     * The number of alerts to keep in the database.
     *
     * @var integer
     */
    private int $alertsToKeep;

    /**
     * An abstract function that assigns all the properties from ENV variables.
     *
     * @return void
     */
    protected function assignProperties(): void
    {
        $this->alertsToKeep = $this->getPropertyValueFromEnv('ALERTS_TO_KEEP', 100);
        $this->databaseServiceUser
            = $this->getPropertyValueFromEnv('MARIADB_SERVICE_USER');
        $this->databaseServicePassword
            = $this->getPropertyValueFromEnv('MARIADB_SERVICE_PASSWORD');
        $this->databaseHost = $this->getPropertyValueFromEnv('MARIADB_SERVICE_HOST');
        $this->databasePort
            = $this->getPropertyValueFromEnv('MARIADB_SERVICE_PORT', 3306);
        $this->databaseName = $this->getPropertyValueFromEnv('MARIADB_DATABASE');
    }

    /**
     * Get the alertsToKeep.
     *
     * @return string
     */
    public function getAlertsToKeep(): string
    {
        return $this->alertsToKeep;
    }

    /**
     * Get the databaseServiceUser.
     *
     * @return string
     */
    public function getDatabaseServiceUser(): string
    {
        return $this->databaseServiceUser;
    }

    /**
     * Get the databaseServicePassword.
     *
     * @return string
     */
    public function getDatabaseServicePassword(): string
    {
        return $this->databaseServicePassword;
    }

    /**
     * Get the databaseHost.
     *
     * @return string
     */
    public function getDatabaseHost(): string
    {
        return $this->databaseHost;
    }

    /**
     * Get the databasePort.
     *
     * @return int
     */
    public function getDatabasePort(): int
    {
        return $this->databasePort;
    }

    /**
     * Get the databaseName.
     *
     * @return string
     */
    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    /**
     * Get the connection array used by
     * Doctrine\DBAL\DriverManager\DriverManager::getConnection.
     *
     * @return array
     */
    public function getConnectionArray(): array
    {
        return [
            // TODO: Create non-root user.
            'user'     => $this->getDatabaseServiceUser(),
            'password' => $this->getDatabaseServicePassword(),
            'host'     => $this->getDatabaseHost(),
            'port'     => $this->getDatabasePort(),
            'dbname'   => $this->getDatabaseName(),
            'driver'   => 'pdo_mysql',
        ];
    }
}
