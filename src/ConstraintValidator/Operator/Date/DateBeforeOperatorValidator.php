<?php

namespace Unleash\Client\ConstraintValidator\Operator\Date;

/**
 * @internal
 */
final class DateBeforeOperatorValidator extends AbstractDateOperatorValidator
{
    protected function validate(string $currentValue, array|string $searchInValue): bool
    {
        assert(is_string($searchInValue));

        return $this->convert($currentValue) < $this->convert($searchInValue);
    }
}
