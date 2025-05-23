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
    $logConfig->getLogPath(),
    $logConfig->getLogRetentionDays(),
    $logConfig->getLogLevel()
)->withName('AlertCleanup');

$db = new Database($logger, $dbConfig);
$db->deleteOldAlerts($dbConfig->getAlertsToKeep());
