<?php
require_once 'vendor/autoload.php';

use Bcgov\NaadConnector\{
    LoggerFactory, 
    Database, 
    Config\DatabaseConfig, 
    Config\LoggerConfig
};

$dbConfig = new DatabaseConfig();
$logConfig = new LoggerConfig('database');
$logger = LoggerFactory::createLogger(
    $logConfig->logPath,
    $logConfig->logRetentionDays,
    $logConfig->logLevel
)->withName('AlertCleanup');

(new Database($logger, $dbConfig))->deleteOldAlerts();
