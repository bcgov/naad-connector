<?php
namespace Bcgov\NaadConnector;

use Monolog\{Level, Logger};
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;

/**
 * A class for creating Monolog logger instances
 * with configurable channel name and logging level.
 *
 * @category Logging
 * @package  NaadConnector
 * @author   Kyle Shapka <Kyle.Shapka@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 */
class LoggerFactory
{

    /**
     * Create a Monolog/Logger instance.
     *
     * @param string $level The minimum logging level to record.
     *
     * @return Logger
     */
    public static function createLogger(
        string $level = 'info'
    ) {
        $processors = [
            new PsrLogMessageProcessor(),
        ];

        $logLevel = self::_convertLogLevel($level);
        $handlers = [
            new StreamHandler('php://stdout', $logLevel),
        ];

        return new Logger('monolog', $handlers, $processors);
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
