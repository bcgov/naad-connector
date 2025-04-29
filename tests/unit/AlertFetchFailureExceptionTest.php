<?php declare(strict_types=1);

namespace Bcgov\NaadConnector\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Bcgov\NaadConnector\Exception\AlertFetchFailureException;

use PHPUnit\Framework\Attributes\{
    Test,
    CoversClass,
};

/**
 * AlertFetchFailureExceptionTest Class for testing AlertFetchFailureException.
 *
 * @category Exception
 * @package  NaadConnector
 * @author   Kyle Shapka <Kyle.Shapka@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 */
#[CoversClass('Bcgov\NaadConnector\Exception\AlertFetchFailureException')]
final class AlertFetchFailureExceptionTest extends TestCase
{
    /**
     * Test that the constructor includes the previous exception's message
     * when one is provided.
     * 
     * @return void
     */
    #[Test]
    public function testConstructorIncludesPreviousMessage(): void
    {
        $previous = new \RuntimeException('Something went wrong');
        $exception = new AlertFetchFailureException($previous);

        $this->assertInstanceOf(AlertFetchFailureException::class, $exception);
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertStringContainsString(
            'Something went wrong',
            $exception->getMessage()
        );
    }

    /**
     * Test that the constructor works without a previous exception,
     * and includes a generic failure message.
     * 
     * @return void
     */
    #[Test]
    public function testConstructorWithNoPrevious(): void
    {
        $exception = new AlertFetchFailureException();

        $this->assertInstanceOf(AlertFetchFailureException::class, $exception);
        $this->assertNull($exception->getPrevious());
        $this->assertStringContainsString(
            'Failed to fetch alert',
            $exception->getMessage()
        );
    }
}
