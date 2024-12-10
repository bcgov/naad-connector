<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\{
    Test,
    CoversClass,
    DataProvider,
    UsesClass
};
use PHPUnit\Framework\TestCase;
use Bcgov\NaadConnector\{
    Database,
    DestinationClient
};
use Bcgov\NaadConnector\Entity\Alert;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * DestinationClientTest Class for testing DestinationClient.
 *
 * @category Client
 * @package  NaadConnector
 * @author   Kyle Shapka <Kyle.Shapka@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 */
#[CoversClass('Bcgov\NaadConnector\DestinationClient')]
#[UsesClass('Bcgov\NaadConnector\Entity\Alert')]
final class DestinationClientTest extends TestCase
{
    /**
     * Tests the sendAlerts method.
     * 
     * @param $alertData an array of response data simulating
     *                   sent alerts.
     * 
     * @return void
     */
    #[Test]
    #[DataProvider('sendAlertsDataProvider')]
    public function testSendAlerts(array $alertData)
    {
        $alert = $this->createMock(Alert::class);

        // Configure the Alert mock behavior.
        $alert->method('getBody')->willReturn($alertData['body']);

        if ($alertData['success']) {
            $alert->expects($this->once())->method('setSuccess')->with(true);
            $alert->expects($this->never())->method('incrementFailures');
        } else {
            $alert->expects($this->once())->method('setSuccess')->with(false);
            $alert->expects($this->once())->method('incrementFailures');
        }

        $alert->expects($this->once())->method('setSendAttempted');
        
        $database = $this->createMock(Database::class);
        $database->method('getUnsentAlerts')->willReturn([$alert]);
        $database->expects($this->once())->method('updateAlert')->with($alert);

        // Configure the HTTP client.
        $httpClient = $this->createMock(Client::class);

        if (isset($alertData['exception'])) {
            $httpClient->method('post')->willThrowException($alertData['exception']);
        } else {
            $httpClient->method('post')->willReturn(
                new Response(
                    $alertData['status_code'],
                    [],
                    $alertData['response_body']
                )
            );
        }

        // Instantiate the DestinationClient.
        $destinationClient = new DestinationClient(
            'http://example.com',
            'user',
            'pass',
            $database
        );

        // Inject the mock HTTP client directly.
        $reflection = new \ReflectionClass(DestinationClient::class);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($destinationClient, $httpClient);

        // Execute the method and assert the results.
        $result = $destinationClient->sendAlerts();
        $this->assertEquals($alertData['expected_result'], $result);
    }

    /**
     * Data provider for the testSendAlerts method.
     *
     * @return array
     */
    public static function sendAlertsDataProvider(): array
    {
        return [
            'Success - alert sent' => [
                [
                    'body' => '<alert>data</alert>',
                    'status_code' => 200,
                    'response_body' => 'OK',
                    'success' => true,
                    'expected_result' => true,
                ],
            ],
            'Failure - alert not sent' => [
                [
                    'body' => '<alert>data</alert>',
                    'status_code' => 500,
                    'response_body' => 'Internal Server Error',
                    'success' => false,
                    'expected_result' => false,
                ],
            ],
            'Connection error' => [
                [
                    'body' => '<alert>data</alert>',
                    'exception' => new ConnectException(
                        'Connection error',
                        new Request('POST', 'http://example.com')
                    ),
                    'success' => false,
                    'expected_result' => false,
                ],
            ],
        ];
    }

    /**
     * Tests the sendAlerts method when a database exception occurs.
     *
     * @return void
     */
    #[Test]
    public function testSendAlertsDatabaseException()
    {
        $alert = $this->createMock(Alert::class);
        $alert->method('getBody')->willReturn('<alert>data</alert>');
        $alert->expects($this->once())->method('setSendAttempted');
        
        $database = $this->createMock(Database::class);
        $database->method('getUnsentAlerts')->willReturn([$alert]);
        $database->method('updateAlert')
            ->willThrowException(new \Exception('Database error'));
    
        $httpClient = $this->createMock(Client::class);
        $httpClient->method('post')->willReturn(
            new Response(200, [], 'OK')
        );
    
        $destinationClient = new DestinationClient(
            'http://example.com',
            'user',
            'pass',
            $database
        );
    
        $reflection = new \ReflectionClass(DestinationClient::class);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($destinationClient, $httpClient);
    
        $result = $destinationClient->sendAlerts();
        $this->assertFalse(
            $result,
            'sendAlerts should return false if a database exception occurs'
        );
    }
    
}