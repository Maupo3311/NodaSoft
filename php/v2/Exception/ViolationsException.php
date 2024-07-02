<?php

namespace NW\WebService\References\Operations\Notification\Exception;

use Exception;
use NW\WebService\References\Operations\Notification\Enum\HttpCodeEnum;

class ViolationsException extends Exception
{
    private $violations;

    public function __construct(array $violations)
    {
        $this->violations = $violations;
        parent::__construct('Validation error', HttpCodeEnum::BAD_REQUEST);
    }

    public function getViolations(): array
    {
        return $this->violations;
    }
}
