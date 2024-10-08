<?php
namespace Bcgov\NaadConnector;

use Monolog\{Level, Logger};
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;

/**
 * A class for setting up a custom Monolog logger instance
 * with configurable channel name and logging level.
 *
 * @category Logging
 * @package  NaadConnector
 * @author   Kyle Shapka <Kyle.Shapka@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 */
class CustomLogger
{
    /**
     * Return a Monolog Logger.
     * 
     * @param string $channelName The name of the logging channel.
     * @param string $level       The minimum logging level to record.
     * @param string $logFilePath The path to the log file to write to.
     * 
     * @return Logger
     */
    public static function getLogger(
        string $channelName = 'monolog',
        string $level = 'info',
        string $logFilePath = './naad-socket.log'
    ) {
        // Set up a monolog channel.
        $logger = new Logger($channelName);

        // Processes a record's message according to PSR-3 rules.
        $processor = new PsrLogMessageProcessor();
        $logger->pushProcessor($processor);

        $logLevel = self::_convertLogLevel($level);

        // Store records to stdout.
        $stream = new StreamHandler('php://stdout', $logLevel);

        // Optionally, you can set a custom formatter here.
        // For example, JSON formatter: $stream->setFormatter(new JsonFormatter());
        // Don't forget to include `use Monolog\Formatter\JsonFormatter` if you do.

        $logger->pushHandler($stream);

        // Log to file.
        $file = new StreamHandler($logFilePath, $logLevel);
        $logger->pushHandler($file);
        
        return $logger;
    }

    /**
     * Converts a string to a Monolog level enum.
     *
     * @param string $loggingLevel The desired logging level.
     * 
     * @return int
     */
    private static function _convertLogLevel($loggingLevel)
    {
        $normalizedLevel = strtolower($loggingLevel);

        $logLevels = [
            'emergency' => Level::Emergency,
            'alert'     => Level::Alert,
            'critical'  => Level::Critical,
            'error'     => Level::Error,
            'warning'   => Level::Warning,
            'notice'    => Level::Notice,
            'info'      => Level::Info,
            'debug'     => Level::Debug,
        ];

        return $logLevels[$normalizedLevel] ?? Level::Info;
    }
}
