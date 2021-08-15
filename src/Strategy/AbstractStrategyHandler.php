<?php

namespace Unleash\Client\Strategy;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Strategy;
use Unleash\Client\Enum\ConstraintOperator;

abstract class AbstractStrategyHandler implements StrategyHandler
{
    public function supports(Strategy $strategy): bool
    {
        return $strategy->getName() === $this->getStrategyName();
    }

    protected function findParameter(string $parameter, Strategy $strategy): ?string
    {
        $parameters = $strategy->getParameters();

        return $parameters[$parameter] ?? null;
    }

    protected function validateConstraints(Strategy $strategy, Context $context): bool
    {
        $constraints = $strategy->getConstraints();
        foreach ($constraints as $constraint) {
            $field = $constraint->getContextName();
            $currentValue = $context->findContextValue($field) ?? '';

            $result = in_array($currentValue, $constraint->getValues(), true);
            if ($constraint->getOperator() === ConstraintOperator::NOT_IN) {
                $result = !$result;
            }

            if (!$result) {
                return false;
            }
        }

        return true;
    }
}
