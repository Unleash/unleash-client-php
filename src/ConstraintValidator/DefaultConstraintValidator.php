<?php

namespace Unleash\Client\ConstraintValidator;

use Unleash\Client\Configuration\Context;
use Unleash\Client\ConstraintValidator\Operator\Date\DateAfterOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Date\DateBeforeOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Lists\InListOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Lists\NotInListOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Number\NumberEqualsOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Number\NumberGreaterThanOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Number\NumberGreaterThanOrEqualsOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Number\NumberLowerThanOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Number\NumberLowerThanOrEqualsOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\String\StringContainsOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\String\StringEndsWithOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\String\StringStartsWithOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Version\VersionEqualsOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Version\VersionGreaterThanOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Version\VersionLowerThanOperatorValidator;
use Unleash\Client\DTO\Constraint;
use Unleash\Client\Enum\ConstraintOperator;
use Unleash\Client\Exception\OperatorValidatorException;

final class DefaultConstraintValidator implements ConstraintValidator
{
    public function validateConstraint(Constraint $constraint, Context $context): bool
    {
        $field = $constraint->getContextName();
        $currentValue = $context->findContextValue($field) ?? '';

        $callback = $this->getValidationCallback($constraint->getOperator());
        $valueToPass = method_exists($constraint, 'getSingleValue') && $constraint->getSingleValue() !== null
            ? $constraint->getSingleValue()
            : $constraint->getValues();

        $isCaseInsensitive = method_exists($constraint, 'isCaseInsensitive') && $constraint->isCaseInsensitive();
        if ($isCaseInsensitive) {
            $currentValue = $this->makeCaseInsensitive($currentValue);
            $valueToPass = $valueToPass === null ? null : $this->makeCaseInsensitive($valueToPass);
        }

        // Catch any possible validator exceptions to avoid inverting result that failed due to
        // wrong data being passed to true.
        try {
            $result = $callback($currentValue, $valueToPass);

            $isInverted = method_exists($constraint, 'isInverted') && $constraint->isInverted();
            if ($isInverted) {
                $result = !$result;
            }
        } catch (OperatorValidatorException) {
            $result = false;
        }

        return $result;
    }

    /**
     * @template T of string|array<mixed>
     *
     * @param T $value
     *
     * @return T
     *
     * @noinspection PhpDocSignatureInspection
     */
    private function makeCaseInsensitive(string|array $value): string|array
    {
        if (is_string($value)) {
            return mb_strtolower($value);
        }

        $result = [];
        foreach ($value as $key => $item) {
            assert(is_array($item) || is_string($item));
            $result[$key] = $this->makeCaseInsensitive($item);
        }

        return $result;
    }

    private function getValidationCallback(string $operator): callable
    {
        return match ($operator) {
            // list
            ConstraintOperator::IN_LIST => new InListOperatorValidator(),
            ConstraintOperator::NOT_IN_LIST => new NotInListOperatorValidator(),
            // strings
            ConstraintOperator::STRING_STARTS_WITH => new StringStartsWithOperatorValidator(),
            ConstraintOperator::STRING_ENDS_WITH => new StringEndsWithOperatorValidator(),
            ConstraintOperator::STRING_CONTAINS => new StringContainsOperatorValidator(),
            // numbers
            ConstraintOperator::NUMBER_EQUALS => new NumberEqualsOperatorValidator(),
            ConstraintOperator::NUMBER_GREATER_THAN => new NumberGreaterThanOperatorValidator(),
            ConstraintOperator::NUMBER_GREATER_THAN_OR_EQUALS => new NumberGreaterThanOrEqualsOperatorValidator(),
            ConstraintOperator::NUMBER_LOWER_THAN => new NumberLowerThanOperatorValidator(),
            ConstraintOperator::NUMBER_LOWER_THAN_OR_EQUALS => new NumberLowerThanOrEqualsOperatorValidator(),
            // date
            ConstraintOperator::DATE_AFTER => new DateAfterOperatorValidator(),
            ConstraintOperator::DATE_BEFORE => new DateBeforeOperatorValidator(),
            // version
            ConstraintOperator::VERSION_EQUALS => new VersionEqualsOperatorValidator(),
            ConstraintOperator::VERSION_GREATER_THAN => new VersionGreaterThanOperatorValidator(),
            ConstraintOperator::VERSION_LOWER_THAN => new VersionLowerThanOperatorValidator(),

            default => fn () => false,
        };
    }
}
