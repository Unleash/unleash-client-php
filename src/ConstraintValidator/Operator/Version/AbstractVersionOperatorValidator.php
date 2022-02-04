<?php

namespace Unleash\Client\ConstraintValidator\Operator\Version;

use Unleash\Client\ConstraintValidator\Operator\AbstractOperatorValidator;

/**
 * @internal
 */
abstract class AbstractVersionOperatorValidator extends AbstractOperatorValidator
{
    private const REGEX = '@^([0-9]+)\.([0-9]+)\.([0-9]+)(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+[0-9A-Za-z-]+)?$@';

    protected function acceptsValues(array|string $values): bool
    {
        return is_string($values) && preg_match(self::REGEX, $values);
    }
}
