<?php

namespace NW\WebService\References\Operations\Notification\Constraints;

abstract class AbstractSingletonConstraint implements ConstraintInterface
{
    private static $constraint;

    private function __construct()
    {
        // empty
    }

    public static function init(): self
    {
        if (null === self::$constraint) {
            self::$constraint = new static();
        }
        return self::$constraint;
    }
}
