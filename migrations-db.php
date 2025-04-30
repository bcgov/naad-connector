<?php

// See https://www.doctrine-project.org/projects/doctrine-migrations/en/3.8/reference/configuration.html#simple

use Bcgov\NaadConnector\Config\DatabaseConfig;

$dbConfig = new DatabaseConfig();

return $dbConfig->getConnectionArray();
