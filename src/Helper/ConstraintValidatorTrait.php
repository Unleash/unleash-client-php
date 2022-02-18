<?php

namespace Unleash\Client\Helper;

use Unleash\Client\ConstraintValidator\ConstraintValidator;
use Unleash\Client\ConstraintValidator\DefaultConstraintValidator;

trait ConstraintValidatorTrait
{
    private ?ConstraintValidator $validator = null;

    private function getValidator(): ConstraintValidator
    {
        $this->validator ??= new DefaultConstraintValidator();

        return $this->validator;
    }
}
