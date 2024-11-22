<?php
require_once 'vendor/autoload.php';

/**
 * This script creates a socket to allow manual testing of the
 * NaadConnector application.
 * 
 * Usage: `php src/start_socket_server.php <path to alert xml file>`
 * 
 * A socket will be created on 127.0.0.1:8080 which the application
 * can be configured to connect to instead of the real NAAD socket.
 * This socket will then immediately send the xml provided as a
 * message to be consumed by the application.
 */

$socket = new React\Socket\SocketServer('127.0.0.1:8080');
$alertXmlLocation = $argv[1] ?? null;

if (!$alertXmlLocation) {
    error_log('You must provide a path to an alert XML file.');
    exit();
}

$socket->on(
    'connection',
    function (React\Socket\ConnectionInterface $connection) use ($alertXmlLocation) {
        $connection->write(file_get_contents($alertXmlLocation));

        $connection->close();
    }
);