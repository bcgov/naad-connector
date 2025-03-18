<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\{
  Test,
  CoversClass,
  DataProvider,
  UsesClass,
};

use PHPUnit\Framework\TestCase;
use Bcgov\NaadConnector\NaadVars;
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
final class ApplicationConfigTest extends TestCase
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
        $config = new ApplicationConfig();
        $config->init();
        $expectedProperties = [
          'databaseRootPassword' => 'test_database_root_password',
          'databaseHost' => 'test_database_host',
          'databasePort' => '3306',
          'databaseName' => 'test_database_name',
          'destinationURL' => 'http://0.0.0.0:38080/test/wp-json/naad/v1/alert',
          'destinationUser' => 'test_destination_user',
          'destinationPassword' => 'test_destination_password',
          'naadUrl' => 'test.naad_url.com',
          'naadRepoUrl' => 'test.naad_repo_url.com',
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
        $config = new ApplicationConfig();
        $config->setSecretPath('./tests/data/secret');
        $config->init();
        
        $expectedProperties = [
          'databaseRootPassword' => 'test_mariadb_root_password_from_file',
          'databaseHost' => 'test_database_host',
          'databasePort' => '3306',
          'databaseName' => 'test_database_name',
          'logPath' => '/logs/naad-1/app.log',
          'feedId' => '1',
        ];

        foreach ($expectedProperties as $property => $expectedValue) {
            $this->assertEquals($expectedValue, $config->$property);
        }
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
        $config = new ApplicationConfig();
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
    public function testApplicationConfigConstructor()
    {
        $config = new ApplicationConfig();
        $config->init();
        $this->assertInstanceOf(ApplicationConfig::class, $config);
        $this->assertSame('1', $config->feedId);
        $this->assertSame('/logs/naad-1/app.log', $config->logPath);
    }

}
