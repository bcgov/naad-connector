<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\{
  Test,
  CoversClass,
  DataProvider,
  UsesClass,
};

use PHPUnit\Framework\TestCase;
use Bcgov\NaadConnector\Config\LoggerConfig;
use Dotenv\Dotenv;

/**
 * NaadVarsTest Class for testing NaadVars class.
 * This will test the class constructor, magic getter, and whether
 * and Exception for an invalid property (passed to the getter)
 * will fire off.
 *
 * @category Client
 * @package  NaadConnector
 * @author   Richard O'Brien <Richard.OBrien@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 */
#[CoversClass('Bcgov\NaadConnector\Config\LoggerConfig')]
#[UsesClass('Bcgov\NaadConnector\Config\LoggerConfig')]
final class LoggerConfigTest extends TestCase
{

    /**
     * Set up the test environment by loading the .env.test file
     * and overriding environment variables.
     *
     * This method is called before each test to ensure a
     * consistent test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $dotenv = Dotenv::createMutable(__DIR__, '.env.test');
        $dotenv->load();
        // Override environment variables manually
        foreach ($_ENV as $key => $value) {
            putenv("$key=$value");
        }

    }

    /**
     * Test the magic getter for retrieving properties.
     *
     * @return void
     */
    #[Test]
    public function testMagicGetter(): void
    {
        $config = new LoggerConfig('test-subpath');
        $expectedProperties = [
          'logPath' => '/logs/naad/test-subpath/app.log',
          'logLevel' => 'debug',
          'logRetentionDays' => 7,
        ];

        foreach ($expectedProperties as $property => $expectedValue) {
            $getter = sprintf("get%s", ucfirst($property));
            $this->assertEquals($expectedValue, $config->$getter());
        }
    }


    /**
     * Test the NaadVars Constructor.
     *
     * @return void
     */
    #[Test]
    public function testLoggerConfigConstructor()
    {
        $config = new LoggerConfig();
        $this->assertInstanceOf(LoggerConfig::class, $config);
        $this->assertSame('debug', $config->getLogLevel());
        $this->assertSame(7, $config->getLogRetentionDays());
    }

}
