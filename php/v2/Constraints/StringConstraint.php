<?php

namespace NW\WebService\References\Operations\Notification\Constraints;

class StringConstraint extends AbstractSingletonConstraint
{
    public function isValid($value): bool
    {
        return null === $value || is_string($value);
    }

    public function getMessage(): string
    {
        return 'The value must be of type string';
    }
}
