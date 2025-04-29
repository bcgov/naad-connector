<?php declare(strict_types=1);

namespace Bcgov\NaadConnector\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Bcgov\NaadConnector\Exception\AlertFailureThresholdException;

use PHPUnit\Framework\Attributes\{
    Test,
    CoversClass
};

/**
 * Class for testing AlertFailureThresholdException.
 *
 * @category Exception
 * @package  NaadConnector
 * @author   Kyle Shapka <Kyle.Shapka@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 */
#[CoversClass('Bcgov\NaadConnector\Exception\AlertFailureThresholdException')]
final class AlertFailureThresholdExceptionTest extends TestCase
{
    /**
     * Test that the exception message includes the threshold and alert ID.
     *
     * @return void
     */
    #[Test]
    public function testConstructorIncludesThresholdAndAlertId(): void
    {
        $exception = new AlertFailureThresholdException(3, 'abc-123');

        $this->assertInstanceOf(AlertFailureThresholdException::class, $exception);
        $this->assertStringContainsString(
            'Failure threshold of 3 reached for alert abc-123.',
            $exception->getMessage()
        );
    }

    /**
     * Test that the constructor accepts a previous throwable.
     *
     * @return void
     */
    #[Test]
    public function testConstructorWithPrevious(): void
    {
        $previous = new \RuntimeException('Underlying error');
        $exception = new AlertFailureThresholdException(5, 'xyz-789', $previous);

        $this->assertSame($previous, $exception->getPrevious());
        $this->assertStringContainsString(
            'Failure threshold of 5 reached for alert xyz-789.',
            $exception->getMessage()
        );
    }
}
