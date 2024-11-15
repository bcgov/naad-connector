<?php
require_once 'vendor/autoload.php';

use Bcgov\NaadConnector\CustomLogger;
use Bcgov\NaadConnector\DestinationClient;
use Bcgov\NaadConnector\NaadSocketConnection;

$destinationClient = new DestinationClient($argv[3], $argv[4], $argv[5]);

// If $argv[6] (log location) is given, use it. Otherwise use default.
$socketLogger = $argv[6] ?
    new CustomLogger('NaadSocketConnection', 'info', $argv[6]) :
    new CustomLogger('NaadSocketConnection', 'info');
$connector    = new NaadSocketConnection(
    $argv[1],
    $argv[2],
    $destinationClient,
    $socketLogger
);
return $connector->connect();
