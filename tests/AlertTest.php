<?php
declare(strict_types=1);

namespace Bcgov\NaadConnector\Tests\Entity;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Bcgov\NaadConnector\Entity\Alert;

/**
 * AlertTest Class for testing Alert class.
 *
 * @category Entity
 * @package  NaadConnector
 * @author   Richard O'Brien <Richard.OBrien@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://www.doctrine-project.org/
 */
#[CoversClass(Alert::class)]
final class AlertTest extends TestCase
{
    private Alert $alert;
    const XML_TEST_FILE = './tests/Socket/complete-alert.xml';

    /**
     * Set up the test environment before each test method is run.
     *
     * This method is called before each test method to initialize
     * the test environment.
     * It creates a new instance of the Alert class and assigns it to
     * the $alert property.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->alert = new Alert();
    }

    #[Test]
    #[DataProvider('alertPropertiesProvider')]
    /**
     * Test the properties and methods of the Alert class.
     *
     * This method tests various setter and getter methods of the Alert class,
     * including ID, body, received time, send attempted time, failures,
     * and success status.
     * It also tests edge cases for the failure count.
     *
     * @param string $id       The alert ID
     * @param string $body     The alert body
     * @param int    $failures The number of failures
     * @param bool   $success  The success status
     *
     * @return void
     */
    public function testAlertProperties(
        string $id,
        string $body,
        int $failures,
        bool $success
    ): void {
        $this->alert->setId($id);
        $this->alert->setBody($body);
        $this->alert->setReceived(new \DateTime());
        $this->alert->setSendAttempted(new \DateTime());
        $this->alert->setFailures($failures);
        $this->alert->setSuccess($success);

        $this->assertEquals($id, $this->alert->getId());
        $this->assertEquals($body, $this->alert->getBody());
        $this->assertInstanceOf(\DateTime::class, $this->alert->getReceived());
        $this->assertInstanceOf(\DateTime::class, $this->alert->getSendAttempted());
        $this->assertEquals(max(0, $failures), $this->alert->getFailures());
        $this->assertEquals($success, $this->alert->getSuccess());

        // Test incrementFailures
        $initialFailures = $this->alert->getFailures();
        $this->alert->incrementFailures();
        $this->assertEquals($initialFailures + 1, $this->alert->getFailures());
    }

    /**
     * Data provider for testAlertProperties
     *
     * @return array
     */
    public static function alertPropertiesProvider(): array
    {
        return [
            ['12345', '<alert>data</alert>', 1, true],
            ['67890', '<alert>other data</alert>', -1, false],
            ['abcde', '<alert>more data</alert>', 100, true],
        ];
    }

    #[Test]
    /**
     * Test the fromXml method of the Alert class with valid XML data.
     *
     * This test method verifies that the Alert::fromXml() method correctly
     * creates an Alert object from a valid XML string. It checks if the
     * created Alert object has the correct identifier, received time,
     * and body content.
     *
     * @return void
     *
     * @throws \Exception If there's an error parsing the XML or creating
     *                    the Alert object.
     */
    public function testFromXmlWithValidData(): void
    {
        $xmlString = file_get_contents(self::XML_TEST_FILE);
        $xml = new \SimpleXMLElement($xmlString);

        $beforeCreation = new \DateTime();
        $alert = Alert::fromXml($xml);
        $afterCreation = new \DateTime();

        $this->assertGreaterThanOrEqual($beforeCreation, $alert->getReceived());
        $this->assertLessThanOrEqual($afterCreation, $alert->getReceived());
        $this->assertEquals('nrcan:eew:1726439000.0', $alert->getId());
        $this->assertStringContainsString(
            '<identifier>nrcan:eew:1726439000.0</identifier>',
            $alert->getBody()
        );
    }

    #[Test]
    /**
     * Test that fromXml method throws an exception when the identifier is empty.
     *
     * This test verifies that the Alert::fromXml() method correctly throws an
     * exception when the XML input contains an empty identifier element. It
     * ensures that the system properly validates the presence of a required field.
     *
     * @return void
     *
     * @throws \Exception Expected to be thrown when the identifier is empty.
     */
    public function testFromXmlThrowsExceptionWhenIdentifierIsEmpty(): void
    {
        $xml = new \SimpleXMLElement('<alert><identifier></identifier></alert>');
        $err = 'Invalid XML: "identifier" field is required and must not be empty.';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($err);

        Alert::fromXml($xml);
    }
}