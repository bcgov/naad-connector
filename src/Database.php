<?php

namespace Bcgov\NaadConnector;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

/**
 * Database class mainly used to handle connecting to the database.
 * 
 * @category Database
 * @package  NaadConnector
 * @author   Michael Haswell <Michael.Haswell@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://www.doctrine-project.org/
 */
class Database
{

    /**
     * Gets an instance of the Doctrine EntityManager.
     *
     * @return EntityManager
     */
    static function getEntityManager(): EntityManager
    {
        // Create a simple "default" Doctrine ORM configuration.
        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [__DIR__ . '/src'],
            isDevMode: true,
        );

        // Configuring the database connection.
        $connection = DriverManager::getConnection(
            [
            // TODO: Create non-root user and replace with env variable.
            'user'     => 'root', 
            'password' => $_ENV['MARIADB_ROOT_PASSWORD'],
            'host'     => $_ENV['MARIADB_SERVICE_HOST'],
            'port'     => $_ENV['MARIADB_SERVICE_PORT'],
            'dbname'   => $_ENV['DATABASE_NAME'],
            'driver'   => 'pdo_mysql',
            ]
        );
        return new EntityManager($connection, $config);
    }
}