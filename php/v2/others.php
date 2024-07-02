<?php

namespace NW\WebService\References\Operations\Notification;

/**
 * @property Seller $seller
 */
class Contractor
{
    const TYPE_CUSTOMER = 0;

    /** @var int */
    public $id;

    /** @var int */
    public $type;

    /** @var string */
    public $name;

    /** @var Seller $seller */
    public $seller;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public static function getById(int $resellerId): ?self
    {
        return new self($resellerId); // fakes the getById method
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFullName(): string
    {
        return null !== $this->name ? $this->name . ' ' . $this->id : $this->id;
    }
}

class Seller extends Contractor
{
}

class Employee extends Contractor
{
}

class Status
{
    public static function getName(int $id): string
    {
        $a = [
            0 => 'Completed',
            1 => 'Pending',
            2 => 'Rejected',
        ];

        return $a[$id];
    }
}

abstract class ReferencesOperation
{
    abstract public function doOperation(): array;
}

function getResellerEmailFrom(string $resellerId): ?string
{
    return 'contractor@example.com';
}

function getEmailsByPermit($resellerId, $event): array
{
    // fakes the method
    return ['someemeil@example.com', 'someemeil2@example.com'];
}

class NotificationEvents
{
    const CHANGE_RETURN_STATUS = 'changeReturnStatus';
    const NEW_RETURN_STATUS    = 'newReturnStatus';
}
