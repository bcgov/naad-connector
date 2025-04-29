<?php

namespace Bcgov\NaadConnector\Exception;

use RunTimeException;

/**
 * Exception which results from failing to fetch an alert.
 *
 * @category Exception
 * @package  NaadConnector
 * @author   Kyle Shapka <Kyle.Shapka@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://www.doctrine-project.org/
 */
class AlertFetchFailureException extends RunTimeException
{
    /**
     * Constructor for the AlertFetchFailureException class.
     *
     * @param \Throwable|null $previous The exception causing the failure, if any.
     */
    public function __construct( \Throwable $previous = null)
    {
        $message = 'Failed to fetch alert';

        if ($previous !== null) {
            $message .= ': ' . $previous->getMessage();
        }

        parent::__construct($message, 0, $previous);
    }
}
