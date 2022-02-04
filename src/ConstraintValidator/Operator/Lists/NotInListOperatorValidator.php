<?php

namespace Unleash\Client\ConstraintValidator\Operator\Lists;

/**
 * @internal
 */
final class NotInListOperatorValidator extends AbstractListOperatorValidator
{
    protected function validate(string $currentValue, array|string $searchInValue): bool
    {
        assert(is_array($searchInValue));

        return !in_array($currentValue, $searchInValue, true);
    }
}
