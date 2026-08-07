<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class HolidayServiceUnavailableException extends RuntimeException
{
    public function __construct(
        string $message = 'Il servizio dei giorni festivi non è disponibile. Riprova più tardi.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
