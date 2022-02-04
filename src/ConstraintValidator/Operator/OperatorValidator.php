<?php

namespace Unleash\Client\ConstraintValidator\Operator;

/**
 * @internal
 */
interface OperatorValidator
{
    /**
     * @param array<mixed>|string|null $allowedValues
     */
    public function __invoke(string $currentValue, array|string|null $allowedValues): bool;
}
