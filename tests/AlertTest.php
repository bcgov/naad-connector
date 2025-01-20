<?php
declare(strict_types=1);

namespace Bcgov\NaadConnector\Tests\Entity;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Bcgov\NaadConnector\Entity\Alert;

/**
 * AlertTest Class for testing Alert class.
 *
 * @category Entity
 * @package  NaadConnector
 * @author   Michael Haswell <Michael.Haswell@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://www.doctrine-project.org/
 */
#[CoversClass(Alert::class)]
final class AlertTest extends TestCase
{
    private Alert $alert;

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
    /**
     * Test the properties and methods of the Alert class.
     *
     * This method tests various setter and getter methods of the Alert class,
     * including ID, body, received time, send attempted time, failures,
     * and success status.
     * It also tests edge cases for the failure count.
     *
     * @return void
     */
    public function testAlertProperties(): void
    {
        $this->alert->setId('12345');
        $this->alert->setBody('<alert>data</alert>');
        $this->alert->setReceived(new \DateTime());
        $this->alert->setSendAttempted(new \DateTime());
        $this->alert->incrementFailures();
        $this->alert->setSuccess(true);

        $this->assertEquals('12345', $this->alert->getId());
        $this->assertEquals('<alert>data</alert>', $this->alert->getBody());
        $this->assertInstanceOf(\DateTime::class, $this->alert->getReceived());
        $this->assertInstanceOf(\DateTime::class, $this->alert->getSendAttempted());
        $this->assertEquals(1, $this->alert->getFailures());
        $this->assertTrue($this->alert->getSuccess());

        // Test failure count edge cases
        $this->alert->setFailures(-1);
        $this->assertEquals(0, $this->alert->getFailures());
        $this->alert->setFailures(100);
        $this->assertEquals(100, $this->alert->getFailures());
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
        $xmlString = '<?xml version="1.0" encoding="UTF-8"?>
        <alert>
            <identifier>12345</identifier>
            <sender>Example Sender</sender>
            <sent>2023-05-01T12:00:00-00:00</sent>
            <status>Actual</status>
            <msgType>Alert</msgType>
            <scope>Public</scope>
        </alert>';

        $xml = new \SimpleXMLElement($xmlString);

        $beforeCreation = new \DateTime();
        $alert = Alert::fromXml($xml);
        $afterCreation = new \DateTime();

        $this->assertGreaterThanOrEqual($beforeCreation, $alert->getReceived());
        $this->assertLessThanOrEqual($afterCreation, $alert->getReceived());
        $this->assertEquals('12345', $alert->getId());
        $this->assertStringContainsString(
            '<identifier>12345</identifier>',
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
        $xmlString = '<?xml version="1.0" encoding="UTF-8"?>
        <alert>
            <identifier></identifier>
        </alert>';

        $xml = new \SimpleXMLElement($xmlString);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'Invalid XML: The "identifier" field is required.'
        );

        Alert::fromXml($xml);
    }
}