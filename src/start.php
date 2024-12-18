<?php
require_once 'vendor/autoload.php';

use Bcgov\NaadConnector\CustomLogger;
use Bcgov\NaadConnector\Database;
use Bcgov\NaadConnector\DestinationClient;
use Bcgov\NaadConnector\NaadSocketClient;
use Bcgov\NaadConnector\NaadSocketConnection;
use Bcgov\NaadConnector\NaadVars;

// Get environment variables for configuring a socket connection.
$naadVars = new NaadVars();

// Create a new Database instance.
$database = new Database();

// Create a custom logger for the NaadSocketConnection.
$socketLogger = new CustomLogger(
    'NaadSocketConnection',
    'info',
);

// Create a new DestinationClient instance with the provided configuration.
$destinationClient = new DestinationClient(
    $naadVars->destinationURL,
    $naadVars->destinationUser,
    $naadVars->destinationPassword,
    $socketLogger,
    $database
);

$socketClient = new NaadSocketClient(
    $naadVars->naadName,
    $destinationClient,
    $socketLogger,
    $database
);

$connector = new NaadSocketConnection(
    $naadVars->naadUrl,
    new React\Socket\Connector(),
    $socketClient,
    $socketLogger,
);

return $connector->connect();

