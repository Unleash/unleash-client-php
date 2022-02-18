<?php

namespace Unleash\Client\ConstraintValidator\Operator\Lists;

use Unleash\Client\ConstraintValidator\Operator\AbstractOperatorValidator;

/**
 * @internal
 */
abstract class AbstractListOperatorValidator extends AbstractOperatorValidator
{
    protected function acceptsValues(array|string $values): bool
    {
        return is_array($values) && array_is_list($values);
    }
}
