<?php

namespace Unleash\Client\Tests\TestHelpers;

use Unleash\Client\ConstraintValidator\Operator\Number\AbstractNumberOperatorValidator;

final class AbstractNumberOperatorValidatorImpl73 extends AbstractNumberOperatorValidator
{
    public function convert(string $number)
    {
        return parent::convert($number);
    }

    protected function validate(string $currentValue, $searchInValue): bool
    {
        return false;
    }
}
