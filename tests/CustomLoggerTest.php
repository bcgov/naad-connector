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
use Bcgov\NaadConnector\CustomLogger;

/**
 * CustomLogger Class for testing CustomLogger class.
 * This will test the class constructor, and _convertLogLevel
 * which is a private method
 *
 * @category Client
 * @package  NaadConnector
 * @author   Richard O'Brien <Richard.OBrien@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 */
#[CoversClass('Bcgov\NaadConnector\CustomLogger')]
#[UsesClass('Bcgov\NaadConnector\CustomLogger')]
final class CustomLoggerTest extends TestCase
{

    /**
     * Test the CustomLogger Constructor.
     *
     * @return void
     */
    #[Test]
    public function testDefaultConstructor()
    {
        $logger = new CustomLogger();

        // Check if the handlers are configured as expected.
        $handlers = $logger->getHandlers();
        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);

        // Assert the default logging level is 'info'
        $streamHandler = $handlers[0];
        $this->assertEquals(Level::Info, $streamHandler->getLevel());

        // Assert that the default channel name is 'monolog'.
        $this->assertEquals('monolog', $logger->getName());
    }

    /**
     * Test the CustomLogger Constructor with parameters.
     * this will instantiate the CustomLogger with:
     * - channelName: 'my_channel'
     * - logLevel: 'debug'
     *
     * @return void
     */
    #[Test]
    public function testCustomConstructor()
    {
        $logger = new CustomLogger('my_channel', 'debug');

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
        $logger = new CustomLogger('test', $levelString);
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