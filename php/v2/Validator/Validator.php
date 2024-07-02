<?php

namespace NW\WebService\References\Operations\Notification\Validator;

use LogicException;
use NW\WebService\References\Operations\Notification\Constraints\ConstraintInterface;
use NW\WebService\References\Operations\Notification\Constraints\TemplateConstraint;

class Validator
{
    public function validate(TemplateConstraint $templateConstraint, array $data): array
    {
        $violations = [];
        foreach ($templateConstraint->getTemplate() as $key => $constraints) {
            if (!is_array($constraints)) {
                $constraints = [$constraints];
            }
            foreach ($constraints as $constraint) {
                if (!$constraint instanceof ConstraintInterface) {
                    throw new LogicException('Unexpected type received. Expected type ' . ConstraintInterface::class);
                }
                $value = $data[$key] ?? null;
                if ($constraint instanceof TemplateConstraint) {
                    $templateViolations = $this->validate($constraint, $constraint->getTemplate());
                    if (!empty($templateViolations)) {
                        $violations[$key] = $templateViolations;
                    }
                    continue 2;
                } elseif (!$constraint->isValid($value)) {
                    $violations[$key] = $constraint->getMessage();
                    continue 2;
                }
            }
        }
        return $violations;
    }
}
