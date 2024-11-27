<?php
require_once 'vendor/autoload.php';

use Bcgov\NaadConnector\CustomLogger;
use Bcgov\NaadConnector\DestinationClient;
use Bcgov\NaadConnector\NaadSocketConnection;
use Bcgov\NaadConnector\NaadVars;

// Get environment variables for configuring a socket connection.
$naadVars = new NaadVars();

// Create a new DestinationClient instance with the provided configuration.
$destinationClient = new DestinationClient(
    $naadVars->destinationURL,
    $naadVars->destinationUser,
    $naadVars->destinationPassword,
);

// Create a custom logger for the NaadSocketConnection
$socketLogger = new CustomLogger(
    'NaadSocketConnection',
    'info',
    $naadVars->logFilePath
);


$connector    = new NaadSocketConnection(
    $naadVars->naadName,
    $naadVars->naadUrl,
    $destinationClient,
    $socketLogger
);

return $connector->connect();


