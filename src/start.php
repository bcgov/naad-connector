<?php
require_once 'vendor/autoload.php';

use Bcgov\NaadConnector\CustomLogger;
use Bcgov\NaadConnector\DestinationClient;
use Bcgov\NaadConnector\NaadSocketClient;

$destinationClient = new DestinationClient($argv[3], $argv[4], $argv[5]);

$socketLogger = CustomLogger::getLogger('NaadSocketClient', 'info', $argv[6]);
$connector = new NaadSocketClient($argv[1], $argv[2], $destinationClient, $socketLogger);
return $connector->connect();