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
    // TODO: Replace with env variable.
    'password' => '$z8ez=^)|YPPD]9NsJ*!.IWOpD.lgK(yH\k9J777g8(i3OJW[',
    // TODO: Replace with env variable.
    'host'     => 'naad-mariadb',
    'port'     => 3306,
    ]
);

// Obtaining the Entity Manager.
$entityManager = new EntityManager($connection, $config);

// Should print the MariaDB version (11.5.2)
$version = $connection->getServerVersion();
error_log($version);

return 0;
