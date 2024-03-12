<?php
require_once 'vendor/autoload.php';

use Bcgov\NaadConnector\DestinationClient;
use Bcgov\NaadConnector\NaadSocketClient;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;

$destinationClient = new DestinationClient($argv[3], $argv[4], $argv[5]);

// Set up a monolog channel.
$logger = new Logger('monolog');

// Processes a record's message according to PSR-3 rules.
$processor = new PsrLogMessageProcessor();
$logger->pushProcessor($processor);

// Store records to stdout.
$stream = new StreamHandler('php://stdout', Level::Info);
$logger->pushHandler($stream);

$connector = new NaadSocketClient($argv[1], $argv[2], $destinationClient, $logger);
return $connector->connect();