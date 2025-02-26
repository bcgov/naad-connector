
<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

require_once 'vendor/autoload.php';

$socket = new React\Socket\SocketServer('0.0.0.0:8080');
$logger = new Logger(
    'e2e',
    [new StreamHandler('php://stdout')],
    [new PsrLogMessageProcessor(),]
);
$logger->info(
    'Starting socket server at {address}.',
    ['address' => $socket->getAddress()]
);

$path = 'tests/data/';
$file = $path . 'earthquake-1.xml';
$socket->on(
    'connection',
    function (React\Socket\ConnectionInterface $conn) use ($file, $logger) {
        $logger->info('Writing {file}.', ['file' => $file]);
        $conn->write(file_get_contents($file));
    }
);