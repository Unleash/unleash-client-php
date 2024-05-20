<?php

namespace Unleash\Client\ConstraintValidator\Operator\Date;

use Override;

/**
 * @internal
 */
final class DateBeforeOperatorValidator extends AbstractDateOperatorValidator
{
    #[Override]
    protected function validate(string $currentValue, array|string $searchInValue): bool
    {
        assert(is_string($searchInValue));

        return $this->convert($currentValue) < $this->convert($searchInValue);
    }
}
