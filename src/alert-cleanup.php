<?php
require_once 'vendor/autoload.php';

use Bcgov\NaadConnector\{LoggerFactory, Database, NaadVars};

$naadVars = new NaadVars();
$logger = LoggerFactory::createLogger(
    $naadVars->logPath,
    $naadVars->logRetentionDays,
    $naadVars->logLevel
)->withName('AlertCleanup');

(new Database($logger))->deleteOldAlerts();
