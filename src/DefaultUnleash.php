<?php

namespace Rikudou\Unleash;

use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\Strategy;
use Rikudou\Unleash\Repository\UnleashRepository;
use Rikudou\Unleash\Strategy\StrategyHandler;

final class DefaultUnleash implements Unleash
{
    /**
     * @param iterable<StrategyHandler> $strategyHandlers
     */
    public function __construct(
        private iterable $strategyHandlers,
        private UnleashRepository $repository,
    ) {
    }

    public function isEnabled(string $featureName, UnleashContext $context = null, bool $default = false): bool
    {
        if ($context === null) {
            $context = new UnleashContext();
        }

        $feature = $this->repository->findFeature($featureName);
        if ($feature === null) {
            return $default;
        }

        if (!$feature->isEnabled()) {
            return false;
        }

        $strategies = $feature->getStrategies();
        if (!is_countable($strategies)) {
            $strategies = iterator_to_array($strategies);
        }
        if (!count($strategies)) {
            return true;
        }

        foreach ($strategies as $strategy) {
            $handlers = $this->findStrategyHandlers($strategy);
            if (!count($handlers)) {
                continue;
            }
            foreach ($handlers as $handler) {
                if ($handler->isEnabled($strategy, $context)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<StrategyHandler>
     */
    private function findStrategyHandlers(Strategy $strategy): array
    {
        $handlers = [];
        foreach ($this->strategyHandlers as $strategyHandler) {
            if ($strategyHandler->supports($strategy)) {
                $handlers[] = $strategyHandler;
            }
        }

        return $handlers;
    }
}
