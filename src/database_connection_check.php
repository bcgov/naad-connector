<?php
require_once 'vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

// Create a simple "default" Doctrine ORM configuration.
$config = ORMSetup::createAttributeMetadataConfiguration(
    paths: [__DIR__ . '/src'],
    isDevMode: true,
);

// Configuring the database connection.
$connection = DriverManager::getConnection(
    [
    'driver'   => 'pdo_mysql',
    // TODO: Create non-root user and replace with env variable.
    'user'     => 'root',
    'password' => $_ENV['MARIADB_ROOT_PASSWORD'],
    'host'     => $_ENV['MARIADB_SERVICE_HOST'],
    'port'     => $_ENV['MARIADB_SERVICE_PORT'],
    ]
);

// Obtaining the Entity Manager.
$entityManager = new EntityManager($connection, $config);

// Should print the MariaDB version (11.5.2)
$version = $connection->getServerVersion();
print_r($version);

return 0;
