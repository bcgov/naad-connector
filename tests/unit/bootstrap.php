/**
 * Bootstrap file for unit tests.
 *
 * This script performs the following tasks:
 * - Loads the Composer autoloader to include dependencies.
 * - Clears all environment variables from the $_ENV superglobal to ensure
 *   a clean testing environment.
 *
 */
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

foreach (array_keys($_ENV) as $key) {
    unset($_ENV[$key]);
}
