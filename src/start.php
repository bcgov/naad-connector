<?php
require_once 'vendor/autoload.php';

// Require headers.php from /src directory
$headers = include dirname(__DIR__) . '/src/headers.php';

use Bcgov\NaadConnector\CustomLogger;
use Bcgov\NaadConnector\Database;
use Bcgov\NaadConnector\DestinationClient;
use Bcgov\NaadConnector\NaadSocketClient;
use Bcgov\NaadConnector\NaadSocketConnection;
use Bcgov\NaadConnector\NaadVars;
use Bcgov\NaadConnector\NaadRepositoryClient;

use GuzzleHttp\Client;

// Get environment variables for configuring a socket connection.
$naadVars = new NaadVars();

// Create a new Database instance.
$database = new Database();

// Create a new Guzzle Client.
$guzzleClient = new Client();

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
    $database,
    $guzzleClient,
    $headers // secure headers for api requests
);

// Create a new RepositoryClient instance with the provided configuration.
$repositoryClient = new NaadRepositoryClient(
    $guzzleClient,
    $naadVars->naadRepoUrl
);

$socketClient = new NaadSocketClient(
    $naadVars->naadName,
    $destinationClient,
    $socketLogger,
    $database,
    $repositoryClient,
);

$reactConnector = new React\Socket\Connector();
$connector = new NaadSocketConnection(
    $naadVars->naadUrl,
    $reactConnector,
    $socketClient,
    $socketLogger,
);

return $connector->connect();

