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
     * Test the _convertLogLevel(string $loggingLevel) method
     * to ensure it correctly converts the logging level to the Monolog Level.
     *
     * @return void
     */
    #[Test]
    public function testLogLevelConversion()
    {
        $logLevels = $this->getLogLevelMappings();

        foreach ($logLevels as $levelString => $expectedLevel) {
            $logger = $this->createLoggerWithLevel($levelString);
            $actualLevel = $this->getLoggerHandlerLevel($logger);

            $this->assertEquals(
                $expectedLevel,
                $actualLevel,
                "Failed asserting that '$levelString' maps to the correct level."
            );
        }
    }

    /**
     * Returns a mapping of log level strings to their corresponding
     * Monolog Level constants.
     *
     * @return array<string, int>
     */
    private function getLogLevelMappings()
    {
        return [
          'emergency' => Level::Emergency,
          'alert'     => Level::Alert,
          'critical'  => Level::Critical,
          'error'     => Level::Error,
          'warning'   => Level::Warning,
          'notice'    => Level::Notice,
          'info'      => Level::Info,
          'debug'     => Level::Debug,
        ];
    }

    /**
     * Creates a CustomLogger instance with the specified log level.
     *
     * @param string $levelString The log level string (e.g. 'debug', 'info', etc.)
     *
     * @return CustomLogger A CustomLogger instance with the specified log level
     */
    private function createLoggerWithLevel(string $levelString)
    {
        return new CustomLogger('test', $levelString);
    }

    /**
     * Retrieves the logging level of the first handler associated with
     * the given CustomLogger instance.
     *
     * @param CustomLogger $logger The CustomLogger instance to
     *                             retrieve the logging level from.
     *
     * @return int The logging level of the first handler.
     */
    private function getLoggerHandlerLevel(CustomLogger $logger)
    {
        $handlers = $logger->getHandlers();
        return $handlers[0]->getLevel();
    }


    /**
     * Test invalid log level.
     * Expect that an invalid logging level string will cause
     * the level to be set to 'info'.
     *
     * @return void
     */
    #[Test]
    public function testInvalidLogLevel()
    {
        $logger = new CustomLogger('test', 'invalidLevel');
        $handlers = $logger->getHandlers();
        $streamHandler = $handlers[0];

        // Assert that an invalid log level will result in the level set to 'info'.
        $this->assertEquals(Level::Info, $streamHandler->getLevel());
    }

    /**
     * Test logger output matches expected output
     *
     * @return void
     */
    #[Test]
    public function testLoggerOutput()
    {
        // Set up a logger with an in-memory stream
        $stream = $this->createInMemoryStream();
        $logger = $this->createLoggerWithStream($stream);

        // Log an 'info' level message
        $logMessage = 'Test info message';
        $logger->info($logMessage);

        // Read the log output.
        $logOutput = $this->getStreamContents($stream);

        // Assert the log contains the expected output
        $expectedOutput = 'test_channel.info: test info message';
        $this->assertStringContainsString(
            strtolower($expectedOutput), strtolower($logOutput)
        );

    }

    /**
     * Creates an in-memory stream for testing purposes.
     *
     * @return resource The in-memory stream.
     */
    private function createInMemoryStream()
    {
        return fopen('php://memory', 'rw');
    }

    /**
     * Creates a CustomLogger instance with the specified stream.
     *
     * @param resource $stream The stream to use for logging.
     *
     * @return CustomLogger A CustomLogger instance with the specified stream.
     */
    private function createLoggerWithStream($stream)
    {
        $handler = new StreamHandler($stream, \Monolog\Level::Info);
        $logger = new CustomLogger('test_channel');
        $logger->setHandlers([$handler]);
        return $logger;
    }

    /**
     * Retrieves the contents of a stream.
     *
     * @param resource $stream The stream to retrieve the contents from.
     *
     * @return string The contents of the stream.
     */
    private function getStreamContents($stream)
    {
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);
        return $contents;
    }
}