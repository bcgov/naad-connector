<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\{
  Test,
  CoversClass,
  DataProvider,
  UsesClass,
};

use PHPUnit\Framework\TestCase;
use Monolog\Level;
use Monolog\Handler\StreamHandler;
use Bcgov\NaadConnector\LoggerFactory;
use Monolog\Handler\RotatingFileHandler;

/**
 * Tests LoggerFactory class.
 * This will test the createLogger and _convertLogLevel functions.
 *
 * @category Client
 * @package  NaadConnector
 * @author   Richard O'Brien <Richard.OBrien@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 */
#[CoversClass('Bcgov\NaadConnector\LoggerFactory')]
#[UsesClass('Bcgov\NaadConnector\LoggerFactory')]
final class LoggerFactoryTest extends TestCase
{

    /**
     * Test the createLogger() function.
     *
     * @return void
     */
    #[Test]
    public function testCreateLoggerDefaults()
    {
        $logger = LoggerFactory::createLogger(logPath: 'fake/path');

        // Check if the handlers are configured as expected.
        $handlers = $logger->getHandlers();
        $this->assertCount(2, $handlers);
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);
        $this->assertInstanceOf(RotatingFileHandler::class, $handlers[1]);

        // Assert the default logging level is 'info'
        $streamHandler = $handlers[0];
        $this->assertEquals(Level::Info, $streamHandler->getLevel());

        // Assert that the default channel name is 'monolog'.
        $this->assertEquals('monolog', $logger->getName());
    }

    /**
     * Test the createLogger() function with parameters.
     * this will instantiate the Logger with:
     * - channelName: 'my_channel'
     * - logLevel: 'debug'
     *
     * @return void
     */
    #[Test]
    public function testCreateLogger()
    {
        $logger = LoggerFactory::createLogger(logPath: 'fake/path', level: 'debug');
        $logger = $logger->withName('my_channel');

        // Assert that the channel name has been set to 'my_channel'.
        $this->assertEquals('my_channel', $logger->getName());

        // Assert the default logging level is 'Debug'
        $handlers = $logger->getHandlers();
        $streamHandler = $handlers[0];
        $this->assertEquals(Level::Debug, $streamHandler->getLevel());
    }

    /**
     * Provides a list of log levels for testing the _convertLogLevel method.
     *
     * @return array A list of log levels with their corresponding Monolog Level.
     */
    public static function logLevelProvider(): array
    {
        return [
            'Emergency' => ['emergency', Level::Emergency],
            'Alert' => ['alert', Level::Alert],
            'Critical' => ['critical', Level::Critical],
            'Error' => ['error', Level::Error],
            'Warning' => ['warning', Level::Warning],
            'Notice' => ['notice', Level::Notice],
            'Info' => ['info', Level::Info],
            'Debug' => ['debug', Level::Debug],
            'Empty String' => ['', Level::Info],
            'Invalid Level Name' =>
            ['I am once again asking for a valid log level', Level::Info],
        ];
    }

    /**
     * Ensures _convertLogLevel correctly maps log level strings to Monolog levels,
     * defaulting to 'info' for invalid/empty inputs.
     *
     * @param string        $levelString   Input logging level string.
     * @param Monolog\Level $expectedLevel Expected Monolog level constant.
     *
     * @return void
     */
    #[Test]
    #[DataProvider('logLevelProvider')]
    public function testLogLevelConversion(
        string $levelString,
        Monolog\Level $expectedLevel
    ): void {
        $logger = LoggerFactory::createLogger(
            logPath: 'fake/path',
            level: $levelString
        );
        $actualLevel = $logger->getHandlers()[0]->getLevel();

        $this->assertEquals(
            $expectedLevel,
            $actualLevel,
            <<<MESSAGE
            Failed log level conversion test:
            Input Level:    '{$levelString}'
            Expected Level: '{$expectedLevel->getName()}'
            Actual Level:   '{$actualLevel->getName()}'
            MESSAGE
        );
    }


}
