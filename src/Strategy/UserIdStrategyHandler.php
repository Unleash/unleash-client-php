<?php

namespace Rikudou\Unleash\Strategy;

use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\Strategy;
use Rikudou\Unleash\Exception\MissingArgumentException;

final class UserIdStrategyHandler extends AbstractStrategyHandler
{
    /**
     * @throws MissingArgumentException
     */
    public function isEnabled(Strategy $strategy, UnleashContext $context): bool
    {
        if (!$userIds = $this->findParameter('userIds', $strategy)) {
            throw new MissingArgumentException("The remote server did not return 'userIds' config");
        }
        if ($context->getCurrentUserId() === null) {
            throw new MissingArgumentException('The user id strategy needs a current user id set in the context');
        }

        $userIds = array_map('trim', explode(',', $userIds));

        return in_array($context->getCurrentUserId(), $userIds, true);
    }

    protected function getStrategyName(): string
    {
        return 'userWithId';
    }
}
