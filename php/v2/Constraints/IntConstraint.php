<?php

namespace NW\WebService\References\Operations\Notification\Constraints;

class IntConstraint extends AbstractSingletonConstraint
{
    public function isValid($value): bool
    {
        return null === $value || is_int($value);
    }

    public function getMessage(): string
    {
        return 'The value must be of type int';
    }
}
