<?php

namespace Rikudou\Unleash;

use Rikudou\Unleash\Client\RegistrationService;
use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\Strategy;
use Rikudou\Unleash\Repository\UnleashRepository;
use Rikudou\Unleash\Strategy\StrategyHandler;

final class DefaultUnleash implements Unleash
{
    /**
     * @param iterable<StrategyHandler> $strategyHandlers
     *
     * @internal
     */
    public function __construct(
        private iterable $strategyHandlers,
        private UnleashRepository $repository,
        private RegistrationService $registrationService,
        bool $autoregister
    ) {
        if ($autoregister) {
            $this->register();
        }
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
            // @codeCoverageIgnoreStart
            $strategies = iterator_to_array($strategies);
            // @codeCoverageIgnoreEnd
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

    public function register(): bool
    {
        return $this->registrationService->register($this->strategyHandlers);
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
