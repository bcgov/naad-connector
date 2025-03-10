<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\{
  Test,
  CoversClass,
  DataProvider,
  UsesClass,
};

use PHPUnit\Framework\TestCase;
use Bcgov\NaadConnector\NaadVars;
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
#[CoversClass('Bcgov\NaadConnector\NaadVars')]
#[UsesClass('Bcgov\NaadConnector\NaadVars')]
final class NaadVarsTest extends TestCase
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
        $naadVars = new NaadVars('/not/real/path');
        $expectedProperties = [
          'databaseRootPassword' => 'test_database_root_password',
          'databaseHost' => 'test_database_host',
          'databasePort' => '3306',
          'databaseName' => 'test_database_name',
          'destinationURL' => 'http://0.0.0.0:38080/test/wp-json/naad/v1/alert',
          'destinationUser' => 'test_destination_user',
          'destinationPassword' => 'test_destination_password',
          'naadName' => 'TEST-NAAD-1',
          'naadUrl' => 'test.naad_url.com',
          'naadRepoUrl' => 'test.naad_repo_url.com',
        ];

        foreach ($expectedProperties as $property => $expectedValue) {
            $this->assertEquals($expectedValue, $naadVars->$property);
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
        $naadVars = new NaadVars('./tests/data/secret');
        $expectedProperties = [
          'databaseRootPassword' => 'test_mariadb_root_password_from_file',
          'databaseHost' => 'test_database_host',
          'databasePort' => '3306',
          'databaseName' => 'test_database_name',
          'destinationURL' => 'http://0.0.0.0:38080/test/wp-json/naad/v1/alert',
          'destinationUser' => 'test_destination_user',
          'destinationPassword' => 'test_destination_password_from_file',
          'naadName' => 'TEST-NAAD-1',
          'naadUrl' => 'test.naad_url.com',
          'naadRepoUrl' => 'test.naad_repo_url.com',
        ];

        foreach ($expectedProperties as $property => $expectedValue) {
            $this->assertEquals($expectedValue, $naadVars->$property);
        }
    }

    /**
     * Test the constructor's failsafe exception on a missing .env property
     *
     * @return void
     */
    public function testConstructorThrowsExceptionWhenEnvVariableMissing()
    {
        // Clear a required environment variable to simulate the issue
        putenv('MARIADB_ROOT_PASSWORD'); // Remove this key from the environment
        unset($_ENV['MARIADB_ROOT_PASSWORD']); // Clear $_ENV if needed

        // Assert that the exception is thrown
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Environment variable 'MARIADB_ROOT_PASSWORD' is required."
        );

        // Instantiate NaadVars to trigger the constructor
        new NaadVars();
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
        $naadVars = new NaadVars();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Property 'NonExistentProperty' does not exist."
        );
        $naadVars->NonExistentProperty;
    }

    /**
     * Test the NaadVars Constructor.
     *
     * @return void
     */
    #[Test]
    public function testNaadVarsConstructor()
    {
        $naadVars = new NaadVars();
        $this->assertInstanceOf(NaadVars::class, $naadVars);
        $this->assertSame('TEST-NAAD-1', $naadVars->naadName);
        $this->assertSame('test.naad_url.com', $naadVars->naadUrl);
        $this->assertSame('test.naad_repo_url.com', $naadVars->naadRepoUrl);
    }

}
