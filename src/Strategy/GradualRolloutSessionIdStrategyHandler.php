<?php

namespace Rikudou\Unleash\Strategy;

use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\DefaultStrategy;
use Rikudou\Unleash\DTO\Strategy;
use Rikudou\Unleash\Enum\Stickiness;

final class GradualRolloutSessionIdStrategyHandler extends AbstractStrategyHandler
{
    public function __construct(private GradualRolloutStrategyHandler $rolloutStrategyHandler)
    {
    }

    public function isEnabled(Strategy $strategy, UnleashContext $context): bool
    {
        $transformedStrategy = new DefaultStrategy(
            $this->getStrategyName(),
            [
                'stickiness' => Stickiness::SESSION_ID,
                'groupId' => $strategy->getParameters()['groupId'],
                'rollout' => $strategy->getParameters()['percentage'],
            ]
        );

        return $this->rolloutStrategyHandler->isEnabled($transformedStrategy, $context);
    }

    protected function getStrategyName(): string
    {
        return 'gradualRolloutSessionId';
    }
}
