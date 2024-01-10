<?php
use PHPUnit\Framework\TestCase;
use Bcgov\NaadConnector\NaadRssClient;

final class NaadRssClientTest extends TestCase
{
    const XML_TEST_FILE_LOCATION = './tests/Rss/';
    /**
     * Tests the fetch() function.
     *
     * @dataProvider fetchDataProvider
     * @param string $location
     * @param integer $expected
     * @return void
     */
    public function testFetch(string $location, int $expected): void
    {
        $client = new NaadRssClient(self::XML_TEST_FILE_LOCATION . $location);
        $result = $client->fetch();
        $this->assertSame($expected, $result);
    }

    /**
     * Provides data for testFetch().
     *
     * @return array
     */
    public static function fetchDataProvider(): array {
        return [
            'Success' => [
                'success.xml',
                1
            ],
            'Failed to load' => [
                'does-not-exist.xml',
                2
            ],
            'Invalid XML' => [
                'invalid.xml',
                2
            ]
        ];
    }

    /**
     * Undocumented function
     *
     * @dataProvider getAlertDataProvider
     * @param string $id
     * @param array $expected
     * @return void
     */
    public function testGetAlert(string $id, string|bool $expected = false) {
        $client = new NaadRssClient(self::XML_TEST_FILE_LOCATION . 'success.xml');
        $this->assertSame(1, $client->fetch());

        $result = $client->getAlert($id);
        if ($expected) {
            $this->assertIsObject($result);
            $this->assertSame($expected, $result->id->__toString());
        } else {
            $this->assertFalse($result);
        }
    }

    /**
     * Provides data for testGetAlert().
     *
     * @return array
     */
    public static function getAlertDataProvider(): array {
        return [
            'Success' => [
                'urn:oid:2.49.0.1.124.2069163587.2024',
                'tag:rss.naad-adna.pelmorex.com,2024-01-05:feed.atom/urn:oid:2.49.0.1.124.2069163587.2024'
            ],
            'Success with full id' => [
                'tag:rss.naad-adna.pelmorex.com,2024-01-04:feed.atom/urn:oid:2.49.0.1.124.0452493547.2024',
                'tag:rss.naad-adna.pelmorex.com,2024-01-04:feed.atom/urn:oid:2.49.0.1.124.0452493547.2024'
            ],
            'Id does not exist' => [
                'urn:oid:this-id-does-not-exist'
            ]
        ];
    }
}

