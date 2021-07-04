<?php

namespace Rikudou\Unleash\Strategy;

use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\Strategy;
use Rikudou\Unleash\Enum\Stickiness;
use Rikudou\Unleash\Stickiness\StickinessCalculator;

final class GradualRolloutStrategyHandler extends AbstractStrategyHandler
{
    public function __construct(
        private StickinessCalculator $stickinessCalculator
    ) {
    }

    public function isEnabled(Strategy $strategy, UnleashContext $context): bool
    {
        if (!$stickiness = $this->findParameter('stickiness', $strategy)) {
            return false;
        }
        $groupId = $this->findParameter('groupId', $strategy) ?? '';
        if (!$rollout = $this->findParameter('rollout', $strategy)) {
            return false;
        }

        switch (strtolower($stickiness)) {
            case Stickiness::USER_ID:
                if ($context->getCurrentUserId() === null) {
                    return false;
                }
                $id = $context->getCurrentUserId();
                break;
            case Stickiness::SESSION_ID:
                if ($context->getSessionId() === null) {
                    return false;
                }
                $id = $context->getSessionId();
                break;
            case Stickiness::RANDOM:
                $id = random_int(1, 100);
                break;
            case Stickiness::DEFAULT:
                $id = $context->getCurrentUserId() ?? $context->getSessionId() ?? random_int(1, 100);
                break;
            default:
                $id = $context->findContextValue($stickiness);
                if ($id === null) {
                    return false;
                }
                break;
        }

        $normalized = $this->stickinessCalculator->calculate((string) $id, $groupId);

        return $normalized <= (int) $rollout;
    }

    public function getStrategyName(): string
    {
        return 'flexibleRollout';
    }
}
