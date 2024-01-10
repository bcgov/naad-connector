<?php
require_once 'vendor/autoload.php';

use Bcgov\NaadConnector\NaadSocketClient;

$connector = new NaadSocketClient($argv[1], $argv[2]);
return $connector->connect();