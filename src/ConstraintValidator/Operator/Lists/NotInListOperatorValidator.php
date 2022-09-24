<?php

namespace Unleash\Client\ConstraintValidator\Operator\Lists;

/**
 * @internal
 */
final class NotInListOperatorValidator extends AbstractListOperatorValidator
{
    /**
     * @param mixed[]|string $searchInValue
     */
    protected function validate(string $currentValue, $searchInValue): bool
    {
        assert(is_array($searchInValue));

        return !in_array($currentValue, $searchInValue, true);
    }
}
