<?php

namespace Rikudou\Unleash\Strategy;

use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\Strategy;
use Rikudou\Unleash\Enum\Stickiness;
use Rikudou\Unleash\Exception\InvalidValueException;
use Rikudou\Unleash\Exception\MissingArgumentException;
use Rikudou\Unleash\Stickiness\StickinessCalculator;

final class GradualRolloutStrategyHandler extends AbstractStrategyHandler
{
    public function __construct(
        private StickinessCalculator $stickinessCalculator
    ) {
    }

    /**
     * @throws MissingArgumentException
     */
    public function isEnabled(Strategy $strategy, UnleashContext $context): bool
    {
        if (!$stickiness = $this->findParameter('stickiness', $strategy)) {
            throw new MissingArgumentException("The remote server did not return 'stickiness' config");
        }
        if (!$groupId = $this->findParameter('groupId', $strategy)) {
            throw new MissingArgumentException("The remote server did not return 'groupId' config");
        }
        if (!$rollout = $this->findParameter('rollout', $strategy)) {
            throw new MissingArgumentException("The remote server did not return 'rollout' config");
        }

        switch (strtolower($stickiness)) {
            case Stickiness::USER_ID:
                if ($context->getCurrentUserId() === null) {
                    throw new MissingArgumentException(
                        'The flexible rollout strategy is set to use user id but no user id is present in context'
                    );
                }
                $id = $context->getCurrentUserId();
                break;
            case Stickiness::SESSION_ID:
                if ($context->getSessionId() === null) {
                    throw new MissingArgumentException(
                        'The flexible rollout strategy is set to use session id but no session is started'
                    );
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
                throw new InvalidValueException("Unknown stickiness value: '{$stickiness}'");
        }

        $normalized = $this->stickinessCalculator->calculate((string) $id, $groupId);

        return $normalized <= (int) $rollout;
    }

    protected function getStrategyName(): string
    {
        return 'flexibleRollout';
    }
}
