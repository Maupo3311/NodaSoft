<?php

namespace NW\WebService\References\Operations\Notification\Constraints;

use DateTime;

class DateConstraint implements ConstraintInterface
{
    /** @var string */
    private $format;

    public function __construct(string $format)
    {
        $this->format = $format;
    }

    public function isValid($value): bool
    {
        return null === $value || false !== DateTime::createFromFormat($this->format, $value);
    }

    public function getMessage(): string
    {
        return 'Invalid date format. Expected format: ' . $this->format;
    }
}
