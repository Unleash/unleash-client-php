<?php

namespace Unleash\Client\ConstraintValidator\Operator\Date;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Unleash\Client\ConstraintValidator\Operator\AbstractOperatorValidator;

/**
 * @internal
 */
abstract class AbstractDateOperatorValidator extends AbstractOperatorValidator
{
    protected function acceptsValues(array|string $values): bool
    {
        if (!is_string($values)) {
            return false;
        }

        try {
            new DateTimeImmutable($values);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    protected function convert(string $dateString): DateTimeInterface
    {
        return new DateTimeImmutable($dateString);
    }
}
