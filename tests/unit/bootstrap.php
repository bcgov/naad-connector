<?php
/**
 * Bootstrap file for unit tests.
 *
 * This script performs the following tasks:
 * - Loads the Composer autoloader to include dependencies.
 * - Clears stale environment variables from the $_ENV superglobal to ensure
 *   a clean testing environment.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// List of environment variables to unset
$env_keys = [
    'MARIADB_ROOT_PASSWORD',
    'MARIADB_SERVICE_HOST',
    'MARIADB_SERVICE_PORT',
    'MARIADB_DATABASE',
    'PHPMYADMIN_PORT',
    'PMA_HOST',
    'PMA_PORT',
    'PMA_USER',
    'PMA_PASSWORD',
    'DESTINATION_URL',
    'DESTINATION_USER',
    'DESTINATION_PASSWORD',
    'NAAD_PORT',
    'NAAD_URL',
    'LOG_LEVEL',
    'ALERTS_TO_KEEP',
    'LOG_PATH',
    'LOG_RETENTION_DAYS',
];

// Unset the environment variables and log their status
array_walk($env_keys, function ($key) {
    if (isset($_ENV[$key])) {
        error_log("Unsetting stale environment variable: $key");
        unset($_ENV[$key]);
    } else {
        error_log("Environment variable not found: $key");
    }
});
