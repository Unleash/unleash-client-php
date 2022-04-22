<?php

namespace Unleash\Client;

use Unleash\Client\Client\RegistrationService;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DTO\Strategy;
use Unleash\Client\DTO\Variant;
use Unleash\Client\Event\FeatureToggleDisabledEvent;
use Unleash\Client\Event\FeatureToggleNoStrategyHandlerEvent;
use Unleash\Client\Event\FeatureToggleNotFoundEvent;
use Unleash\Client\Event\FeatureVariantBeforeFallbackReturnedEvent;
use Unleash\Client\Event\UnleashEvents;
use Unleash\Client\Metrics\MetricsHandler;
use Unleash\Client\Repository\UnleashRepository;
use Unleash\Client\Strategy\StrategyHandler;
use Unleash\Client\Variant\VariantHandler;

final class DefaultUnleash implements Unleash
{
    /**
     * @param iterable<StrategyHandler> $strategyHandlers
     */
    public function __construct(
        private readonly iterable $strategyHandlers,
        private readonly UnleashRepository $repository,
        private readonly RegistrationService $registrationService,
        private readonly UnleashConfiguration $configuration,
        private readonly MetricsHandler $metricsHandler,
        private readonly VariantHandler $variantHandler,
    ) {
        if ($configuration->isAutoRegistrationEnabled()) {
            $this->register();
        }
    }

    public function isEnabled(string $featureName, ?Context $context = null, bool $default = false): bool
    {
        $context ??= $this->configuration->getContextProvider()->getContext();

        $feature = $this->repository->findFeature($featureName);
        if ($feature === null) {
            $event = new FeatureToggleNotFoundEvent($context, $featureName);
            $event = $this->configuration->getEventDispatcher()->dispatch(
                $event,
                UnleashEvents::FEATURE_TOGGLE_NOT_FOUND,
            );
            assert($event instanceof FeatureToggleNotFoundEvent);
            if ($event->isEnabled() !== null) {
                return $event->isEnabled();
            }

            if ($event->getFeature() === null) {
                return $default;
            }
            $feature = $event->getFeature();
        }

        if (!$feature->isEnabled()) {
            $event = new FeatureToggleDisabledEvent($feature, $context);
            $event = $this->configuration->getEventDispatcher()->dispatch(
                $event,
                UnleashEvents::FEATURE_TOGGLE_DISABLED,
            );
            assert($event instanceof FeatureToggleDisabledEvent);
            $feature = $event->getFeature();

            if (!$feature->isEnabled()) {
                $this->metricsHandler->handleMetrics($feature, false);

                return false;
            }
        }

        $strategies = $feature->getStrategies();
        if (!is_countable($strategies)) {
            $strategies = iterator_to_array($strategies);
        }
        if (!count($strategies)) {
            $this->metricsHandler->handleMetrics($feature, true);

            return true;
        }

        $handlersFound = false;
        foreach ($strategies as $strategy) {
            $handlers = $this->findStrategyHandlers($strategy);
            if (!count($handlers)) {
                continue;
            }
            $handlersFound = true;
            foreach ($handlers as $handler) {
                if ($handler->isEnabled($strategy, $context)) {
                    $this->metricsHandler->handleMetrics($feature, true);

                    return true;
                }
            }
        }

        if (!$handlersFound) {
            $event = new FeatureToggleNoStrategyHandlerEvent($context);
            $event = $this->configuration->getEventDispatcher()->dispatch(
                $event,
                UnleashEvents::FEATURE_TOGGLE_NO_STRATEGY_HANDLER,
            );
            assert($event instanceof FeatureToggleNoStrategyHandlerEvent);

            $strategyHandler = $event->getStrategyHandler();
            if ($strategyHandler !== null) {
                foreach ($strategies as $strategy) {
                    if ($strategyHandler->isEnabled($strategy, $context)) {
                        $this->metricsHandler->handleMetrics($feature, true);

                        return true;
                    }
                }
            }
        }

        $this->metricsHandler->handleMetrics($feature, false);

        return false;
    }

    public function getVariant(string $featureName, ?Context $context = null, ?Variant $fallbackVariant = null): Variant
    {
        $fallbackVariant ??= $this->variantHandler->getDefaultVariant();
        $context ??= $this->configuration->getContextProvider()->getContext();

        $feature = $this->repository->findFeature($featureName);
        if ($feature === null || !$feature->isEnabled() || !count($feature->getVariants())) {
            $event = new FeatureVariantBeforeFallbackReturnedEvent(
                $fallbackVariant,
                $feature,
                $context,
            );
            $event = $this->configuration->getEventDispatcher()->dispatch(
                $event,
                UnleashEvents::FEATURE_VARIANT_BEFORE_FALLBACK_RETURNED,
            );
            assert($event instanceof FeatureVariantBeforeFallbackReturnedEvent);

            return $event->getFallbackVariant();
        }

        $variant = $this->variantHandler->selectVariant($feature, $context);
        if ($variant !== null) {
            $this->metricsHandler->handleMetrics($feature, true, $variant);
        } else {
            $event = new FeatureVariantBeforeFallbackReturnedEvent(
                $fallbackVariant,
                $feature,
                $context,
            );
            $event = $this->configuration->getEventDispatcher()->dispatch(
                $event,
                UnleashEvents::FEATURE_VARIANT_BEFORE_FALLBACK_RETURNED,
            );
            assert($event instanceof FeatureVariantBeforeFallbackReturnedEvent);

            return $event->getFallbackVariant();
        }

        return $variant;
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
