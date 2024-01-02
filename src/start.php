<?php
require_once 'vendor/autoload.php';

use Bcgov\NaadConnector\NaadClient;

$connector = new NaadClient($argv[1], $argv[2]);
return $connector->connect();