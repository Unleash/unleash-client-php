<?php

namespace Unleash\Client\Tests\TestHelpers;

use Unleash\Client\ConstraintValidator\Operator\Number\AbstractNumberOperatorValidator;

final class AbstractNumberOperatorValidatorImpl80 extends AbstractNumberOperatorValidator
{
    public function convert(string $number): int|float
    {
        return parent::convert($number);
    }

    protected function validate(string $currentValue, array|string $searchInValue): bool
    {
        return false;
    }
}
