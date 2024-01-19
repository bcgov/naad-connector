<?php
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
        $client = new NaadSocketClient('test-naad', 'testing.url');

        foreach ($xmlResponses as $response) {
            $xml = file_get_contents(self::XML_TEST_FILE_LOCATION . $response['location']);
            $result = $method->invokeArgs($client, [$xml]);
            $this->assertSame($response['expected'], $result);
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
                        'location' => 'single.xml',
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
                        'location' => 'single.xml',
                        'expected' => true
                    ],
                ]
            ]  
        ];
    }
}

