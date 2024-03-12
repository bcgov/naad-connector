<?php
require_once 'vendor/autoload.php';

use Bcgov\NaadConnector\DestinationClient;
use Bcgov\NaadConnector\NaadSocketClient;

$destinationClient = new DestinationClient($argv[3], $argv[4], $argv[5]);

$connector = new NaadSocketClient($argv[1], $argv[2], $destinationClient);
return $connector->connect();