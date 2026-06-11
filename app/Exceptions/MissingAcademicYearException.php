<?php

namespace App\Exceptions;

use Exception;

class MissingAcademicYearException extends Exception
{
    public function __construct($message = "No active academic year configured in the system. Please set an academic year as current.")
    {
        parent::__construct($message);
    }
}
