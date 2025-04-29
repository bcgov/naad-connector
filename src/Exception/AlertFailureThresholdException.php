<?php

namespace Bcgov\NaadConnector\Exception;

use RunTimeException;

/**
 * Exception which results from failing to send an alert too many times.
 *
 * @category Exception
 * @package  NaadConnector
 * @author   Kyle Shapka <Kyle.Shapka@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://www.doctrine-project.org/
 */
class AlertFailureThresholdException extends RunTimeException
{
    /**
     * Constructor for the AlertFailureThresholdException class.
     *
     * @param integer         $threshold The threshold before an alert send
     *                                   attempt is considered a failure.
     * @param string          $alertId   The unique ID of the alert to be sent.
     * @param \Throwable|null $previous  The exception causing the failure, if any.
     */
    public function __construct(
        int $threshold,
        string $alertId,
        \Throwable $previous = null
    ) {
        $message = sprintf(
            'Failure threshold of %d reached for alert %s.',
            $threshold,
            $alertId
        );

        parent::__construct($message, 0, $previous);
    }
}
