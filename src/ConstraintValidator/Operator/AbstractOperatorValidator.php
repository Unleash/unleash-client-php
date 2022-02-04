<?php

namespace Unleash\Client\ConstraintValidator\Operator;

/**
 * @internal
 */
abstract class AbstractOperatorValidator implements OperatorValidator
{
    /**
     * @param array<mixed>|string|null $allowedValues
     */
    public function __invoke(string $currentValue, array|string|null $allowedValues): bool
    {
        if ($allowedValues === null) {
            return false;
        }

        if ($this->isMultiple($allowedValues)) {
            assert(is_array($allowedValues));

            foreach ($allowedValues as $allowedValue) {
                assert(is_string($allowedValue) || is_array($allowedValue));

                if ($this->validate($currentValue, $allowedValue)) {
                    return true;
                }
            }

            return false;
        }

        if (!$this->acceptsValues($allowedValues)) {
            return false;
        }

        return $this->validate($currentValue, $allowedValues);
    }

    /**
     * @param array<mixed>|string $values
     */
    abstract protected function acceptsValues(array|string $values): bool;

    /**
     * @param string|array<mixed> $searchInValue
     */
    abstract protected function validate(string $currentValue, array|string $searchInValue): bool;

    /**
     * @param array<mixed>|string $allowedValues
     */
    private function isMultiple(array|string $allowedValues): bool
    {
        return is_array($allowedValues) && !$this->acceptsValues($allowedValues);
    }
}
