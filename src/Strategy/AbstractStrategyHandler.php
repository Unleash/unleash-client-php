<?php

namespace Unleash\Client\Strategy;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Strategy;
use Unleash\Client\Helper\ConstraintValidatorTrait;

abstract class AbstractStrategyHandler implements StrategyHandler
{
    use ConstraintValidatorTrait;

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
            if (!$this->getValidator()->validateConstraint($constraint, $context)) {
                return false;
            }
        }

        return true;
    }
}
