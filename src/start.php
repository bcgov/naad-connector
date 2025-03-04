<?php
require_once 'vendor/autoload.php';

// Require headers.php from /src directory
$headers = include dirname(__DIR__) . '/src/headers.php';

use Bcgov\NaadConnector\LoggerFactory;
use Bcgov\NaadConnector\Database;
use Bcgov\NaadConnector\DestinationClient;
use Bcgov\NaadConnector\NaadSocketClient;
use Bcgov\NaadConnector\NaadSocketConnection;
use Bcgov\NaadConnector\NaadVars;
use Bcgov\NaadConnector\NaadRepositoryClient;

use GuzzleHttp\Client;

// Get environment variables for configuring a socket connection.
$naadVars = new NaadVars();

// Create loggers for each component.
$logger = LoggerFactory::createLogger(
    $naadVars->logPath,
    $naadVars->logRetentionDays,
    $naadVars->logLevel
);
$naadSocketConnectionLogger = $logger->withName('NaadSocketConnection');
$destinationClientLogger    = $logger->withName('DestinationClient');
$naadSocketClientLogger     = $logger->withName('NaadSocketClient');
$databaseLogger             = $logger->withName('Database');
$repositoryClientLogger     = $logger->withName('NaadRepositoryClient');

// Create a new Database instance.
$database = new Database($databaseLogger);

// Create a new Guzzle Client.
$guzzleClient = new Client();

// configure the guzzle client for the DestinationClient
$destinationGuzzleclient = new Client(
    [
    'base_uri' => $naadVars->destinationURL,
    'auth'     => [$naadVars->destinationUser, $naadVars->destinationPassword],
    'headers'  => $headers // secure headers for api requests
    ]
);


// Create a new DestinationClient instance with the provided configuration.
$destinationClient = new DestinationClient(
    $destinationClientLogger,
    $database,
    $destinationGuzzleclient,
);

// Create a new RepositoryClient instance with the provided configuration.
$repositoryClient = new NaadRepositoryClient(
    $guzzleClient,
    $naadVars->naadRepoUrl,
    $repositoryClientLogger
);

$socketClient = new NaadSocketClient(
    $naadVars->naadName,
    $destinationClient,
    $naadSocketClientLogger,
    $database,
    $repositoryClient,
);

$reactConnector = new React\Socket\Connector();
$connector = new NaadSocketConnection(
    $naadVars->naadUrl,
    $reactConnector,
    $socketClient,
    $naadSocketConnectionLogger,
);

return $connector->connect();
