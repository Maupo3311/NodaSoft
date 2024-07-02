<?php

namespace NW\WebService\References\Operations\Notification\Constraints;

interface ConstraintInterface
{
    public function isValid($value): bool;

    public function getMessage(): string;
}
