<?php

namespace NW\WebService\References\Operations\Notification\Constraints;

class OneOfConstraint implements ConstraintInterface
{
    /** @var array */
    private $validValues;

    public function __construct(array $validValues)
    {
        $this->validValues = $validValues;
    }

    public function isValid($value): bool
    {
        return null === $value || in_array($value, $this->validValues);
    }

    public function getMessage(): string
    {
        return 'The value must be one of: ' . implode(', ', $this->validValues);
    }
}
