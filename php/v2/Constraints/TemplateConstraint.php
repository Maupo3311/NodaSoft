<?php

namespace NW\WebService\References\Operations\Notification\Constraints;

class TemplateConstraint implements ConstraintInterface
{
    /** @var array<string, ConstraintInterface> */
    private $template;

    public function __construct(array $template)
    {
        $this->template = $template;
    }

    public function getTemplate(): array
    {
        return $this->template;
    }

    public function isValid($value): bool
    {
        return false;
    }

    public function getMessage(): string
    {
        return '';
    }
}
