<?php

namespace Unleash\Client\ConstraintValidator\Operator\Date;

/**
 * @internal
 */
final class DateBeforeOperatorValidator extends AbstractDateOperatorValidator
{
    /**
     * @param mixed[]|string $searchInValue
     */
    protected function validate(string $currentValue, $searchInValue): bool
    {
        assert(is_string($searchInValue));

        return $this->convert($currentValue) < $this->convert($searchInValue);
    }
}
