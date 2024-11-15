<?php

// See https://www.doctrine-project.org/projects/doctrine-migrations/en/3.8/reference/configuration.html#simple

return [
    'user'     => 'root',
    'password' => $_ENV['MARIADB_ROOT_PASSWORD'],
    'host'     => $_ENV['MARIADB_SERVICE_HOST'],
    'port'     => $_ENV['MARIADB_SERVICE_PORT'],
    'dbname'   => $_ENV['MARIADB_DATABASE'],
    'driver'   => 'pdo_mysql',
];
