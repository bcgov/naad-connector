
<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require_once 'vendor/autoload.php';

/**
 * This script creates a socket to allow manual testing of the
 * NaadConnector application.
 * 
 * Usage: `php src/start_socket_server.php <path to alert xml file>`
 * 
 * A socket will be created on 0.0.0.0:8080 which the application
 * can be configured to connect to instead of the real NAAD socket.
 * This socket will then immediately send the xml provided as a
 * message to be consumed by the application.
 */
$socket = new React\Socket\SocketServer('0.0.0.0:8080');
$path = 'tests/data/';

$logger = new Logger('e2e', [new StreamHandler('php://stdout')]);
$logger->info('Starting socket server.');
$logger->info($socket->getAddress());

$file = $path . 'earthquake-1.xml';
$file2 = $path . 'earthquake-2.xml';
$socket->on(
    'connection',
    function (React\Socket\ConnectionInterface $conn) use ($file, $logger) {
        $logger->info('Writing ' . $file);
        $conn->write(file_get_contents($file));
    }
);