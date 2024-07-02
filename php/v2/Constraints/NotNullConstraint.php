<?php

namespace NW\WebService\References\Operations\Notification\Constraints;

class NotNullConstraint extends AbstractSingletonConstraint
{
    public function isValid($value): bool
    {
        return null !== $value;
    }

    public function getMessage(): string
    {
        return 'Value cannot be empty';
    }
}
