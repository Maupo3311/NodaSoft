<?php

namespace NW\WebService\References\Operations\Notification\Enum;

abstract class DoOperationTypeEnum
{
    public const NEW = 1;
    public const CHANGE = 2;

    public static function getValues(): array
    {
        return [
            self::NEW,
            self::CHANGE,
        ];
    }
}
