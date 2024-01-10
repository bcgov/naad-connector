<?php
require_once 'vendor/autoload.php';

use Bcgov\NaadConnector\NaadRssClient;

$rssClient = new NaadRssClient($argv[1]);
if (1 === $rssClient->fetch()) {
    print_r($rssClient->getAlert($argv[2]));
}
