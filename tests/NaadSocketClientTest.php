<?php declare(strict_types=1);
use PHPUnit\Framework\Attributes\{
    Test,
    CoversClass,
    DataProvider,
    UsesClass
};
use PHPUnit\Framework\TestCase;

use Bcgov\NaadConnector\CustomLogger;
use Bcgov\NaadConnector\Database;
use Bcgov\NaadConnector\DestinationClient;
use Bcgov\NaadConnector\NaadRepositoryClient;
use Bcgov\NaadConnector\NaadSocketClient;

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
}
