<?php

namespace Unleash\Client\ConstraintValidator;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Constraint;

interface ConstraintValidator
{
    public function validateConstraint(Constraint $constraint, Context $context): bool;
}
