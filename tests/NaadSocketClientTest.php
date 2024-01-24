<?php

use Bcgov\NaadConnector\DestinationClient;
use PHPUnit\Framework\TestCase;
use Bcgov\NaadConnector\NaadSocketClient;

final class NaadSocketClientTest extends TestCase
{
    const XML_TEST_FILE_LOCATION = './tests/Socket/';

    /**
     * Tests validateResponse function.
     *
     * @dataProvider validateResponseDataProvider
     * @param string $id
     * @param array $expected
     * @return void
     */
    public function testValidateResponse(array $xmlResponses) {
        $class = new ReflectionClass('Bcgov\NaadConnector\NaadSocketClient');
        $method = $class->getMethod('validateResponse');
        $client = new NaadSocketClient('test-naad', 'testing.url', new DestinationClient('testing.url', 'user', 'pass'));

        foreach ($xmlResponses as $response) {
            $xml = file_get_contents(self::XML_TEST_FILE_LOCATION . $response['location']);
            $result = $method->invokeArgs($client, [$xml]);
            if (false === $response['expected']) {
                $this->assertSame($response['expected'], $result);
            } else {
                $this->assertIsObject($result);
            }
        }
    }

    /**
     * Provides data for testValidateResponse().
     *
     * @return array
     */
    public static function validateResponseDataProvider(): array {
        return [
            'Success - single part' => [
                [
                    [
                        'location' => 'complete-alert.xml',
                        'expected' => true
                    ]
                ]
            ],
            'Success - multi-part' => [
                [
                    [
                        'location' => 'multipart/1.xml',
                        'expected' => false
                    ],
                    [
                        'location' => 'multipart/success/2.xml',
                        'expected' => true
                    ]
                ]
            ],
            'Invalid - multi-part' => [
                [
                    [
                        'location' => 'multipart/1.xml',
                        'expected' => false
                    ],
                    [
                        'location' => 'multipart/invalid/2.xml',
                        'expected' => false
                    ]
                ]
            ],
            'Success - Recover from invalid alert' => [
                [
                    [
                        'location' => 'multipart/1.xml',
                        'expected' => false
                    ],
                    [
                        'location' => 'multipart/recover/2.xml',
                        'expected' => false
                    ],
                    [
                        'location' => 'complete-alert.xml',
                        'expected' => true
                    ],
                ]
            ],
            'Success - Heartbeat' => [
                [
                    [
                        'location' => 'heartbeat.xml',
                        'expected' => true
                    ],
                ]
            ],
            'Invalid - Incorrect namespace' => [
                [
                    [
                        'location' => 'incorrect-namespace.xml',
                        'expected' => false
                    ],
                ]
            ]
        ];
    }

    /**
     * Tests isHeartbeat function.
     *
     * @return void
     */
    public function testIsHeartbeat() {
        $class = new ReflectionClass('Bcgov\NaadConnector\NaadSocketClient');
        $method = $class->getMethod('isHeartbeat');
        $client = new NaadSocketClient('test-naad', 'testing.url', new DestinationClient('testing.url', 'user', 'pass'));

        // Test that a heartbeat XML returns true.
        $heartbeat = simplexml_load_file(self::XML_TEST_FILE_LOCATION . '/heartbeat.xml');
        $heartbeat->registerXPathNamespace('x', 'urn:oasis:names:tc:emergency:cap:1.2');
        $result = $method->invokeArgs($client, [$heartbeat]);
        $this->assertTrue($result);

        // Test that an alert XML returns false.
        $alert = simplexml_load_file(self::XML_TEST_FILE_LOCATION . '/complete-alert.xml');
        $alert->registerXPathNamespace('x', 'urn:oasis:names:tc:emergency:cap:1.2');
        $result = $method->invokeArgs($client, [$alert]);
        $this->assertFalse($result);
    }
}

