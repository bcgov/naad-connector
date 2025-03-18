<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\{
  Test,
  CoversClass,
  DataProvider,
  UsesClass,
};

use PHPUnit\Framework\TestCase;

use Bcgov\NaadConnector\Config\ApplicationConfig;
use Bcgov\NaadConnector\Config\DatabaseConfig;
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
#[CoversClass('Bcgov\NaadConnector\Config\ApplicationConfig')]
#[UsesClass('Bcgov\NaadConnector\Config\ApplicationConfig')]
final class DatabaseConfigTest extends TestCase
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

        // Load .env.test file
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
        $config = new DatabaseConfig();
        $config->init();
        $expectedProperties = [
          'databaseRootPassword' => 'test_database_root_password',
          'databaseHost' => 'test_database_host',
          'databasePort' => '3306',
          'databaseName' => 'test_database_name',
        ];

        foreach ($expectedProperties as $property => $expectedValue) {
            $this->assertEquals($expectedValue, $config->$property);
        }
    }

    /**
     * Test the magic getter for retrieving properties including one from pas
     *
     * @return void
     */
    #[Test]
    public function testMagicGetterFromFile(): void
    {
        // $config = new NaadVars('./tests/data/secret');
        $config = new DatabaseConfig();
        $config->setSecretPath('./tests/data/secret');
        $config->init();
        
        $expectedProperties = [
          'databaseRootPassword' => 'test_mariadb_root_password_from_file',
          'databaseHost' => 'test_database_host',
          'databasePort' => '3306',
          'databaseName' => 'test_database_name',
          'logPath' => '/logs/naad-database/app.log',
          'feedId' => 'database',
        ];

        foreach ($expectedProperties as $property => $expectedValue) {
            $this->assertEquals($expectedValue, $config->$property);
        }
    }

    /**
     * Test the constructor's failsafe exception on a missing .env property
     *
     * @return void
     */
    public function testGetVariableThrowsExceptionWhenEnvVariableMissing()
    {
        // Clear a required environment variable to simulate the issue
        putenv('MARIADB_ROOT_PASSWORD'); // Remove this key from the environment
        unset($_ENV['MARIADB_ROOT_PASSWORD']); // Clear $_ENV if needed

        // Assert that the exception is thrown
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Property 'databaseRootPassword' does not exist."
        );

        $config = new DatabaseConfig();
        $config->init();
        $config->databaseRootPassword;
    }


    /**
     * Test the magic getter for an Invalid property.
     * Expect exception.
     *
     * @return void
     */
    #[Test]
    public function testMagicGetterForInvalidProperty()
    {
        $config = new DatabaseConfig();
        $config->init();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Property 'NonExistentProperty' does not exist."
        );
        $config->NonExistentProperty;
    }

    /**
     * Test the NaadVars Constructor.
     *
     * @return void
     */
    #[Test]
    public function testDatabaseConfigConstructor()
    {
        $config = new DatabaseConfig();
        $config->init();
        $this->assertInstanceOf(DatabaseConfig::class, $config);
        $this->assertSame('test_database_host', $config->databaseHost);
        $this->assertSame('3306', $config->databasePort);
    }

}
