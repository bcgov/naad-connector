<?php
require_once 'vendor/autoload.php';

use Bcgov\NaadConnector\CustomLogger;
use Bcgov\NaadConnector\DestinationClient;
use Bcgov\NaadConnector\NaadSocketConnection;
use Bcgov\NaadConnector\NaadVars;

// Get environment variables for configuring a socket connection.
$naadVars = new NaadVars();

// Attempt to return a configured socket connection.
    $destinationClient = new DestinationClient(
        $naadVars->destinationURL,
        $naadVars->destinationUser,
        $naadVars->destinationPassword,
    );


    // If $argv[6] (log location) is given, use it. Otherwise use default.
    $socketLogger = $naadVars->logFilePath ?
        new CustomLogger(
            'NaadSocketConnection',
            'info',
            $naadVars->logFilePath
        ) :
        new CustomLogger('NaadSocketConnection', 'info');

    $connector    = new NaadSocketConnection(
        $naadVars->naadName,
        $naadVars->naadUrl,
        $destinationClient,
        $socketLogger
    );
    return $connector->connect();


