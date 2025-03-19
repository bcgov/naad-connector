<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\{
  Test,
  CoversClass,
  DataProvider,
  UsesClass,
};

use PHPUnit\Framework\TestCase;
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
#[CoversClass('Bcgov\NaadConnector\Config\DatabaseConfig')]
#[UsesClass('Bcgov\NaadConnector\Config\DatabaseConfig')]
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
        $expectedProperties = [
          'databaseRootPassword' => 'test_database_root_password',
          'databaseHost' => 'test_database_host',
          'databasePort' => 3306,
          'databaseName' => 'test_database_name',
          'alertsToKeep' => 100
        ];

        foreach ($expectedProperties as $property => $expectedValue) {
            $getter = sprintf("get%s", ucfirst($property));
            $this->assertEquals($expectedValue, $config->$getter());
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
        $config = new DatabaseConfig('./tests/data/secret');
        $expectedProperties = [
            'databaseRootPassword' => 'test_mariadb_root_password_from_file',
            'databaseHost' => 'test_database_host',
            'databasePort' => 3306,
            'databaseName' => 'test_database_name',
            'alertsToKeep' => 100
        ];

        foreach ($expectedProperties as $property => $expectedValue) {
            $getter = sprintf("get%s", ucfirst($property));
            $this->assertEquals($expectedValue, $config->$getter());
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
            "Property 'MARIADB_ROOT_PASSWORD' does not exist."
        );

        $config = new DatabaseConfig();
        $config->getDatabaseRootPassword();
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
        $this->assertInstanceOf(DatabaseConfig::class, $config);
        $this->assertSame('test_database_host', $config->getDatabaseHost());
        $this->assertSame(3306, $config->getDatabasePort());
    }
}
