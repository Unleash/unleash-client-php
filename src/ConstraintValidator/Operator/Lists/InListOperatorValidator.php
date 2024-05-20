<?php

namespace Unleash\Client\ConstraintValidator\Operator\Lists;

use Override;

/**
 * @internal
 */
final class InListOperatorValidator extends AbstractListOperatorValidator
{
    #[Override]
    protected function validate(string $currentValue, array|string $searchInValue): bool
    {
        assert(is_array($searchInValue));

        return in_array($currentValue, $searchInValue, true);
    }
}
