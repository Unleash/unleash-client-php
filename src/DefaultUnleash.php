<?php

namespace Unleash\Client;

use Unleash\Client\Client\RegistrationService;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DTO\DefaultFeatureEnabledResult;
use Unleash\Client\DTO\Dependency;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\FeatureEnabledResult;
use Unleash\Client\DTO\Strategy;
use Unleash\Client\DTO\Variant;
use Unleash\Client\Enum\ImpressionDataEventType;
use Unleash\Client\Event\FeatureToggleDependencyNotFoundEvent;
use Unleash\Client\Event\FeatureToggleDisabledEvent;
use Unleash\Client\Event\FeatureToggleMissingStrategyHandlerEvent;
use Unleash\Client\Event\FeatureToggleNotFoundEvent;
use Unleash\Client\Event\ImpressionDataEvent;
use Unleash\Client\Event\UnleashEvents;
use Unleash\Client\Helper\Uuid;
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
        $feature = $this->findFeature($featureName, $context) ?? null;

        if ($feature !== null) {
            if (method_exists($feature, 'hasImpressionData') && $feature->hasImpressionData()) {
                $event = new ImpressionDataEvent(
                    ImpressionDataEventType::IS_ENABLED,
                    Uuid::v4(),
                    clone $this->configuration,
                    clone $context,
                    clone $feature,
                    null,
                );
                $this->configuration->getEventDispatcherOrNull()?->dispatch($event, UnleashEvents::IMPRESSION_DATA);
            }
        }

        return $this->isFeatureEnabled($feature, $context, $default)->isEnabled();
    }

    public function getVariant(string $featureName, ?Context $context = null, ?Variant $fallbackVariant = null): Variant
    {
        $feature = $this->findFeature($featureName, $context);

        return $this->getVariantForFeature($feature, $context, $fallbackVariant);
    }

    public function register(): bool
    {
        return $this->registrationService->register($this->strategyHandlers);
    }

    /**
     * Checks if parent feature flag requirement is satisfied.
     *
     * @param Dependency $dependency the dependency to check
     * @param Context    $context    the context to use
     */
    public function isDependencySatisfied(
        ?Dependency $dependency = null,
        ?Context $context = null,
    ): bool {
        if ($dependency === null) {
            return true;
        }
        $context ??= $this->configuration->getContextProvider()->getContext();

        $parentFeature = $dependency->getFeature();

        if ($parentFeature == null || is_string($parentFeature)) {
            $event = new FeatureToggleDependencyNotFoundEvent($context, $parentFeature ? $parentFeature : '');
            $this->configuration->getEventDispatcherOrNull()?->dispatch(
                $event,
                UnleashEvents::FEATURE_TOGGLE_NOT_FOUND,
            );

            return false;
        }

        if (count($parentFeature->getDependencies()) > 0) {
            return false;
        }

        $parentFeatureEnabled = $this->isFeatureEnabled($parentFeature, $context);

        if ($parentFeatureEnabled->isEnabled() && $dependency->getEnabled() === false) {
            return false;
        }
        if (!$parentFeatureEnabled->isEnabled() && $dependency->getEnabled() !== false) {
            return false;
        }

        $dependencyVariants = $dependency->getVariants();
        if ($dependencyVariants && count($dependencyVariants) > 0) {
            $parentFeatureVariantName = $this->getVariantForFeature($parentFeature, $context)->getName();

            foreach ($dependencyVariants as $dependencyVariant) {
                if ($dependencyVariant === $parentFeatureVariantName) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    /**
     * Finds a feature with it's parent features. Posts events if the feature is not found.
     *
     * @param string  $featureName name of the feature to find
     * @param Context $context     the context to use
     *
     * @return Feature|null
     */
    private function findFeature(string $featureName, ?Context $context): ?Feature
    {
        $features = $this->repository->getFeatures();
        assert(is_array($features));
        $context ??= $this->configuration->getContextProvider()->getContext();

        $feature = $features[$featureName] ?? null;

        if ($feature === null) {
            $event = new FeatureToggleNotFoundEvent($context, $featureName);
            $this->configuration->getEventDispatcherOrNull()?->dispatch(
                $event,
                UnleashEvents::FEATURE_TOGGLE_NOT_FOUND,
            );

            return null;
        }

        return $feature;
    }

    /**
     * Selects a variant from a feature.
     *
     * @param Feature  $feature         the feature to check
     * @param Context  $context         the context to use
     * @param ?Variant $fallbackVariant the default value to return if the feature is not found
     */
    private function getVariantForFeature(?Feature $feature, ?Context $context = null, ?Variant $fallbackVariant = null): Variant
    {
        $fallbackVariant ??= $this->variantHandler->getDefaultVariant();
        $context ??= $this->configuration->getContextProvider()->getContext();

        $enabledResult = $this->isFeatureEnabled($feature, $context);
        $strategyVariants = $enabledResult->getStrategy()?->getVariants() ?? [];
        if (
            $feature === null || $enabledResult->isEnabled() === false ||
            (!count($feature->getVariants()) && empty($strategyVariants))
        ) {
            return $fallbackVariant;
        }
        $featureName = $feature->getName();

        if (count($strategyVariants) != 0) {
            $variant = $this->variantHandler->selectVariant($strategyVariants, $enabledResult->getStrategy()?->getParameters()['groupId'] ?? '', $context);
        } else {
            $variant = $this->variantHandler->selectVariant($feature->getVariants(), $featureName, $context);
        }

        if ($variant !== null) {
            $this->metricsHandler->handleMetrics($feature, true, $variant);

            if (method_exists($feature, 'hasImpressionData') && $feature->hasImpressionData()) {
                $event = new ImpressionDataEvent(
                    ImpressionDataEventType::GET_VARIANT,
                    Uuid::v4(),
                    clone $this->configuration,
                    clone $context,
                    clone $feature,
                    clone $variant,
                );
                $this->configuration->getEventDispatcherOrNull()?->dispatch($event, UnleashEvents::IMPRESSION_DATA);
            }
        }
        $resolvedVariant = $variant ?? $fallbackVariant;

        return $resolvedVariant;
    }

    /**
     * Underlying method to check if a feature is enabled.
     *
     * @param Feature|null $feature the feature to check
     * @param Context      $context the context to use
     * @param bool         $default the default value to return if the feature is not found
     */
    private function isFeatureEnabled(?Feature $feature, Context $context, bool $default = false): FeatureEnabledResult
    {
        if ($feature === null) {
            return new DefaultFeatureEnabledResult($default);
        }

        if (!$feature->isEnabled()) {
            $event = new FeatureToggleDisabledEvent($feature, $context);
            $this->configuration->getEventDispatcherOrNull()?->dispatch(
                $event,
                UnleashEvents::FEATURE_TOGGLE_DISABLED,
            );

            $this->metricsHandler->handleMetrics($feature, false);

            return new DefaultFeatureEnabledResult();
        }

        $dependencies = $feature->getDependencies();
        if (count($dependencies) > 0) {
            foreach ($dependencies as $dependency) {
                if (!$this->isDependencySatisfied($dependency, $context)) {
                    $this->metricsHandler->handleMetrics($feature, false);

                    return new DefaultFeatureEnabledResult();
                }
            }
        }

        $strategies = $feature->getStrategies();
        if (!is_countable($strategies)) {
            $strategies = iterator_to_array($strategies);
        }
        if (!count($strategies)) {
            $this->metricsHandler->handleMetrics($feature, true);

            return new DefaultFeatureEnabledResult(true);
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

                    return new DefaultFeatureEnabledResult(true, $strategy);
                }
            }
        }

        if (!$handlersFound) {
            $event = new FeatureToggleMissingStrategyHandlerEvent($context, $feature);
            $this->configuration->getEventDispatcherOrNull()?->dispatch(
                $event,
                UnleashEvents::FEATURE_TOGGLE_MISSING_STRATEGY_HANDLER,
            );
        }

        $this->metricsHandler->handleMetrics($feature, false);

        return new DefaultFeatureEnabledResult();
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
