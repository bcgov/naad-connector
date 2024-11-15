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
class CustomLogger extends Logger
{

    /**
     * Construct a CustomLogger.
     *
     * @param string $channelName The name of the logging channel.
     * @param string $level       The minimum logging level to record.
     * @param string $logFilePath The path to the log file to write to.
     *
     * @return Logger
     */
    public function __construct(
        string $channelName = 'monolog',
        string $level = 'info',
        string $logFilePath = './naad-socket.log'
    ) {
        $processors = [
            new PsrLogMessageProcessor(),
        ];

        $logLevel = self::_convertLogLevel($level);
        $handlers = [
            new StreamHandler('php://stdout', $logLevel),
            new StreamHandler($logFilePath, $logLevel)
        ];

        parent::__construct($channelName, $handlers, $processors);
    }

    /**
     * Converts a string to a Monolog level enum.
     *
     * @param string $loggingLevel The desired logging level.
     *
     * @return int
     */
    private static function _convertLogLevel( $loggingLevel )
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

        return $logLevels[ $normalizedLevel ] ?? Level::Info;
    }
}
