<?php declare(strict_types=1);
use PHPUnit\Framework\Attributes\{
    Test,
    CoversClass,
    DataProvider,
    UsesClass
};
use PHPUnit\Framework\TestCase;

use Bcgov\NaadConnector\{
    CustomLogger,
    Database,
    DestinationClient,
    NaadRepositoryClient,
    NaadSocketClient,
    NaadVars,
};
use Bcgov\NaadConnector\Entity\Alert;

#[CoversClass('Bcgov\NaadConnector\NaadSocketClient')]
#[UsesClass('Bcgov\NaadConnector\Entity\Alert')]
final class NaadSocketClientTest extends TestCase {

    const XML_TEST_FILE_LOCATION = './tests/Socket/';

    #[Test]
    #[DataProvider('handleResponseProvider')]
    public function testHandleResponse(array $xmlResponses) {
        $database = $this->createStub( Database::class );
        $destinationClient = $this->createStub( DestinationClient::class );
        $logger = $this->createStub(CustomLogger::class);
        $repositoryClient = $this->createStub( NaadRepositoryClient::class );

        $client = new NaadSocketClient( 'test-naad', $destinationClient, $logger, $database, $repositoryClient );
        libxml_use_internal_errors(true);
        foreach($xmlResponses as $response) {
            $result = $client->handleResponse(file_get_contents( self::XML_TEST_FILE_LOCATION . $response['location'] ));
           // error_log('INSIDE NAADSOCKETCLIENT TEST RESULT: ---- '. $result);
            $this->assertEquals($response['expected'], $result);
        }
    }

    public static function handleResponseProvider() {
        return [
            'Success - single part'                => [
                [
                    [
                        'location' => 'complete-alert.xml',
                        'expected' => true,
                    ],
                ],
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
            ],
            'Success - Recover from invalid alert' => [
                [
                    [
                        'location' => 'multipart/1.xml',
                        'expected' => false,
                    ],
                    [
                        'location' => 'multipart/recover/2.xml',
                        'expected' => false,
                    ],
                    [
                        'location' => 'complete-alert.xml',
                        'expected' => true,
                    ],
                ],
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
            ],
            'Success - Heartbeat'                  => [
                [
                    [
                        'location' => 'heartbeat.xml',
                        'expected' => true,
                    ],
                ],
            ],
            'Invalid - Incorrect namespace'        => [
                [
                    [
                        'location' => 'incorrect-namespace.xml',
                        'expected' => false,
                    ],
                ],
            ],
        ];
    }

    #[Test]
    public function testHandleResponseMissedAlerts() {
        $database = $this->createStub( Database::class );
        $database->method('getAlertsById')->willReturn([]);

        $destinationClient = $this->createStub( DestinationClient::class );
        $logger = $this->createStub( CustomLogger::class );

        $repositoryClient = $this->createMock( NaadRepositoryClient::class );
        // Should fetch 10 times because all 10 heartbeat reference ids are new.
        $repositoryClient
            ->expects($this->exactly(10))
            ->method('fetchAlert')
            ->willReturn(file_get_contents( self::XML_TEST_FILE_LOCATION . 'complete-alert.xml' ));

        $client = new NaadSocketClient( 'test-naad', $destinationClient, $logger, $database, $repositoryClient );
        libxml_use_internal_errors(true);

        $result = $client->handleResponse(file_get_contents( self::XML_TEST_FILE_LOCATION . 'heartbeat.xml' ));
        $this->assertEquals(true, $result);
    }

    #[Test]
    public function testHandleResponseExistingMissedAlert() {
        $alertXml = file_get_contents(self::XML_TEST_FILE_LOCATION . 'complete-alert.xml');

        $database = $this->createStub( Database::class );
        $database
            ->method('getAlertsById')
            ->willReturn([Alert::fromXml(new SimpleXMLElement($alertXml))]);

        $destinationClient = $this->createStub( DestinationClient::class );
        $logger = $this->createStub( CustomLogger::class );

        $repositoryClient = $this->createMock( NaadRepositoryClient::class );
        // Should only fetch 9 times because one was already in the database.
        $repositoryClient
            ->expects($this->exactly(9))
            ->method('fetchAlert');

        $client = new NaadSocketClient( 'test-naad', $destinationClient, $logger, $database, $repositoryClient );
        libxml_use_internal_errors(true);

        $result = $client->handleResponse(file_get_contents( self::XML_TEST_FILE_LOCATION . 'heartbeat.xml' ));
        $this->assertEquals(true, $result);
    }

    #[Test]
    public function testHandleResponseDatabaseException() {
        $database = $this->createStub( Database::class );
        $database->method('insertAlert')->willThrowException(new Exception());
        $destinationClient = $this->createStub( DestinationClient::class );
        $logger = $this->createStub( CustomLogger::class );
        $repositoryClient = $this->createStub( NaadRepositoryClient::class );

        $client = new NaadSocketClient( 'test-naad', $destinationClient, $logger, $database, $repositoryClient );
        libxml_use_internal_errors(true);

        $this->expectException(Exception::class);

        $client->handleResponse(file_get_contents( self::XML_TEST_FILE_LOCATION . 'complete-alert.xml' ));
    }

    #[Test]
    public function testHandleResponseMissingIdentifier() {
        $database = $this->createStub(Database::class);
        $destinationClient = $this->createStub(DestinationClient::class);
        $logger = $this->createStub(CustomLogger::class);
        $repositoryClient = $this->createStub(NaadRepositoryClient::class);

        $client = new NaadSocketClient('test-naad', $destinationClient, $logger, $database, $repositoryClient);
        libxml_use_internal_errors(true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid XML: The "identifier" field is required.');

        $client->handleResponse(file_get_contents(self::XML_TEST_FILE_LOCATION . 'empty-identifier.xml'));
    }
}
