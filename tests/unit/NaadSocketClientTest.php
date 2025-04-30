<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\{
    Test,
    CoversClass,
    DataProvider,
    UsesClass
};
use PHPUnit\Framework\TestCase;
use Monolog\Logger;

use Bcgov\NaadConnector\{
    Database,
    DestinationClient,
    NaadRepositoryClient,
    NaadSocketClient,
};
use Bcgov\NaadConnector\Config\DatabaseConfig;
use Bcgov\NaadConnector\Entity\Alert;

/**
 * NaadSocketClientTest Class for testing NaadSocketClient.
 * Uses the \Entity\Alert class.
 *
 * @category Client
 * @package  NaadConnector
 * @author   Michael Haswell <Michael.Haswell@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 */
#[CoversClass('Bcgov\NaadConnector\NaadSocketClient')]
#[UsesClass('Bcgov\NaadConnector\Entity\Alert')]
#[UsesClass('Bcgov\NaadConnector\Config\DatabaseConfig')]
final class NaadSocketClientTest extends TestCase
{

    const XML_TEST_FILE_LOCATION = './tests/data/';
    /**
     * Tests the handleResponse method of the NaadSocketClient class.
     *
     * This test verifies that the handleResponse method correctly processes
     * various XML responses and returns the expected results. It uses
     * a data provider to supply different XML response scenarios.
     *
     * @param array $xmlResponses An array of XML response data, where each
     *                            entry contains a 'location' for the XML file
     *                            and an 'expected' value for assertion.
     * @param int   $sendAttempts The number of times DestinationClient->sendAlerts()
     *                            should have been called.
     *
     * @return void
     */
    #[Test]
    #[DataProvider('handleResponseProvider')]
    public function testHandleResponse(array $xmlResponses, int $sendAttempts)
    {
        $database = $this->createStub(Database::class, DatabaseConfig::class);
        $database->method('insertAlert')->willReturn(true);
        $destinationClient = $this->createMock(DestinationClient::class);
        $logger = $this->createStub(Logger::class);
        $repositoryClient = $this->createStub(NaadRepositoryClient::class);

        $client = new NaadSocketClient(
            $destinationClient,
            $logger,
            $database,
            $repositoryClient
        );

        $destinationClient
            ->expects($this->exactly($sendAttempts))
            ->method('sendAlerts');

        libxml_use_internal_errors(true);
        foreach ($xmlResponses as $response) {
            $result = $client->handleResponse(
                file_get_contents(
                    self::XML_TEST_FILE_LOCATION . $response['location']
                )
            );
            $this->assertEquals($response['expected'], $result);
        }
    }

    /**
     * Tests the handleResponse method of the NaadSocketClient class.
     *
     * This method tests the handleResponse method with various XML responses.
     * It checks if the method correctly handles different types of responses,
     * including single part responses, multi-part responses,
     * and heartbeat responses.
     *
     * @return array The test responses
     */
    public static function handleResponseProvider()
    {
        return [
            'Success - single part'                => [
                [
                    [
                        'location' => 'complete-alert.xml',
                        'expected' => true,
                    ],
                ],
                1
            ],
            'Success - multi-part'                 => [
                [
                    [
                        'location' => 'multipart/1.xml',
                        'expected' => false,
                    ],
                    [
                        'location' => 'multipart/success/2.xml',
                        'expected' => true,
                    ],
                ],
                1
            ],
            'Invalid - multi-part'                 => [
                [
                    [
                        'location' => 'multipart/1.xml',
                        'expected' => false,
                    ],
                    [
                        'location' => 'multipart/invalid/2.xml',
                        'expected' => false,
                    ],
                ],
                0
            ],
            'Success - Recover from invalid alert' => [
                [
                    [
                        'location' => 'multipart/1.xml',
                        'expected' => false,
                    ],
                    [
                        'location' => 'multipart/invalid/2.xml',
                        'expected' => false,
                    ],
                    [
                        'location' => 'complete-alert.xml',
                        'expected' => true,
                    ],
                ],
                1
            ],
            'Success - Recover from multipart alert heartbeat interruption' => [
                [
                    [
                        'location' => 'multipart/1.xml',
                        'expected' => false,
                    ],
                    [
                        'location' => 'heartbeat.xml',
                        'expected' => false,
                    ],
                    [
                        'location' => 'multipart/success/2.xml',
                        'expected' => false,
                    ],
                    [
                        'location' => 'heartbeat.xml',
                        'expected' => true,
                    ],
                ],
                1
            ],
            'Success - Heartbeat'                  => [
                [
                    [
                        'location' => 'heartbeat.xml',
                        'expected' => true,
                    ],
                ],
                1
            ],
            // Should skip schema validation and return true.
            'Invalid - Incorrect namespace'        => [
                [
                    [
                        'location' => 'incorrect-namespace.xml',
                        'expected' => true,
                    ],
                ],
                1
            ],
            'Invalid - Fails schema validation'    => [
                [
                    [
                        'location' => 'schema-validation-failure.xml',
                        'expected' => false,
                    ],
                ],
                0
            ],
        ];
    }

    #[Test]
    /**
     * Tests the handleResponse method of the NaadSocketClient class when there are
     * missed alerts.
     *
     * This method tests the handleResponse method with a heartbeat response
     * that contains missed alerts.
     * It checks if the method correctly handles the missed alerts by fetching them
     * from the repository.
     *
     * @return void
     */
    public function testHandleResponseMissedAlerts()
    {
        $database = $this->createMock(Database::class);
        $database->method('getAlertsById')->willReturn([]);
        $database->expects($this->exactly(10))->method('insertAlert');

        $destinationClient = $this->createStub(DestinationClient::class);
        $logger = $this->createStub(Logger::class);

        $repositoryClient = $this->createMock(NaadRepositoryClient::class);
        // Should fetch 10 times because all 10 heartbeat reference ids are new.
        $repositoryClient
            ->expects($this->exactly(10))
            ->method('fetchAlert')
            ->willReturn(
                file_get_contents(
                    self::XML_TEST_FILE_LOCATION . 'complete-alert.xml'
                )
            );

        $client = new NaadSocketClient(
            $destinationClient,
            $logger,
            $database,
            $repositoryClient
        );
        libxml_use_internal_errors(true);

        $result = $client->handleResponse(
            file_get_contents(self::XML_TEST_FILE_LOCATION . 'heartbeat.xml')
        );
        $this->assertEquals(true, $result);
    }

    #[Test]
    /**
     * Tests handleResponse when an exception occurs when selecting
     * alerts from the database rethrows the exception.
     *
     * @return void
     */
    public function testHandleResponseMissedAlertsDatabaseException()
    {
        $database = $this->createMock(Database::class);
        $database->method('getAlertsById')->willThrowException(new Exception());
        $destinationClient = $this->createStub(DestinationClient::class);
        $logger = $this->createStub(Logger::class);
        $repositoryClient = $this->createStub(NaadRepositoryClient::class);

        $client = new NaadSocketClient(
            $destinationClient,
            $logger,
            $database,
            $repositoryClient
        );

        $this->expectException(Exception::class);

        $client->handleResponse(
            file_get_contents(self::XML_TEST_FILE_LOCATION . 'heartbeat.xml')
        );
    }

    /**
     * Tests the handleResponse method of the NaadSocketClient class when there are
     * existing missed alerts.
     *
     * This method tests the handleResponse method with a heartbeat response
     * that contains missed alerts, where one of the alerts is already
     * in the database.
     * It checks if the method correctly handles the missed alerts by fetching them
     * from the repository.
     *
     * @return void
     */
    #[Test]
    public function testHandleResponseExistingMissedAlert()
    {
        $alertXml = file_get_contents(
            self::XML_TEST_FILE_LOCATION . 'complete-alert.xml'
        );

        $database = $this->createMock(Database::class);
        $database
            ->method('getAlertsById')
            ->willReturn([Alert::fromXml(new SimpleXMLElement($alertXml))]);
        $database->expects($this->exactly(9))->method('insertAlert');

        $destinationClient = $this->createStub(DestinationClient::class);
        $logger = $this->createStub(Logger::class);

        $repositoryClient = $this->createMock(NaadRepositoryClient::class);
        // Should only fetch 9 times because one was already in the database.
        $repositoryClient
            ->expects($this->exactly(9))
            ->method('fetchAlert')
            ->willReturn($alertXml);

        $client = new NaadSocketClient(
            $destinationClient,
            $logger,
            $database,
            $repositoryClient
        );

        libxml_use_internal_errors(true);

        $result = $client->handleResponse(
            file_get_contents(self::XML_TEST_FILE_LOCATION . 'heartbeat.xml')
        );
        $this->assertEquals(true, $result);
    }

    /**
     * Tests the handleResponse method of the NaadSocketClient class when
     * a database  exception occurs.
     *
     * This method tests the handleResponse method with a valid XML response, but
     * simulates a database exception when trying to insert the
     * alert into the database.
     * It checks if the method correctly handles the database exception and throws
     * an exception.
     *
     * @return void
     */
    #[Test]
    public function testHandleResponseDatabaseException()
    {
        $database = $this->createStub(Database::class);
        $database->method('insertAlert')->willThrowException(new Exception());
        $destinationClient = $this->createStub(DestinationClient::class);
        $logger = $this->createStub(Logger::class);
        $repositoryClient = $this->createStub(NaadRepositoryClient::class);

        $client = new NaadSocketClient(
            $destinationClient,
            $logger,
            $database,
            $repositoryClient
        );

        libxml_use_internal_errors(true);

        $this->expectException(Exception::class);

        $client->handleResponse(
            file_get_contents(self::XML_TEST_FILE_LOCATION . 'complete-alert.xml')
        );
    }

    /**
     * Tests the handleResponse method of the NaadSocketClient class when the XML
     * response is missing the identifier field.
     *
     * This method tests the handleResponse method with an XML response
     * that does not contain the required identifier field.
     * It checks if the method correctly handles the missing identifier
     * field and throws an exception.
     *
     * @return void
     */
    #[Test]
    public function testHandleResponseMissingIdentifier()
    {
        $database = $this->createStub(Database::class);
        $destinationClient = $this->createStub(DestinationClient::class);
        $logger = $this->createStub(Logger::class);
        $repositoryClient = $this->createStub(NaadRepositoryClient::class);
        $err = 'Invalid XML: "identifier" field is required and must not be empty.';

        $client = new NaadSocketClient(
            $destinationClient,
            $logger,
            $database,
            $repositoryClient
        );
        libxml_use_internal_errors(true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($err);

        $client->handleResponse(
            file_get_contents(self::XML_TEST_FILE_LOCATION . 'empty-identifier.xml')
        );
    }

    /**
     * Tests the handleResponse method when inserting an alert into the database
     * results in a duplicate key exception. Receiving this exception should
     * cause the alert not to be sent via DestinationClient.
     *
     * @return void
     */
    #[Test]
    public function testHandleResponseDuplicateAlert()
    {
        $database = $this->createStub(Database::class);
        $database->method('insertAlert')->willReturn(false);
        $destinationClient = $this->createMock(DestinationClient::class);
        $logger = $this->createStub(Logger::class);
        $repositoryClient = $this->createStub(NaadRepositoryClient::class);

        $client = new NaadSocketClient(
            $destinationClient,
            $logger,
            $database,
            $repositoryClient
        );

        $destinationClient
            ->expects($this->exactly(0))
            ->method('sendAlerts');

        $client->handleResponse(
            file_get_contents(self::XML_TEST_FILE_LOCATION . 'complete-alert.xml')
        );
    }

    /**
     * Tests the handleResponse method of the NaadSocketClient class when
     * a database  exception occurs.
     *
     * This method tests the handleResponse method with a valid XML response, but
     * simulates a database exception when trying to insert the
     * alert into the database.
     * It checks if the method correctly handles the database exception and throws
     * an exception.
     *
     * @return void
     */
    #[Test]
    public function testHandleResponseDestinationClientException()
    {
        $database = $this->createStub(Database::class);
        $database->method('insertAlert')->willReturn(true);
        $destinationClient = $this->createStub(DestinationClient::class);
        $destinationClient
            ->method('sendAlerts')
            ->willThrowException(new Exception());
        $logger = $this->createStub(Logger::class);
        $repositoryClient = $this->createStub(NaadRepositoryClient::class);

        $client = new NaadSocketClient(
            $destinationClient,
            $logger,
            $database,
            $repositoryClient
        );

        $this->expectException(Exception::class);

        $client->handleResponse(
            file_get_contents(self::XML_TEST_FILE_LOCATION . 'complete-alert.xml')
        );
    }
}
