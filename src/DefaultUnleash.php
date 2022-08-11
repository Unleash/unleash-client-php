<?php

namespace Unleash\Client;

use Unleash\Client\Client\RegistrationService;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DTO\Strategy;
use Unleash\Client\DTO\Variant;
use Unleash\Client\Enum\ImpressionDataEventType;
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
     * @var iterable<StrategyHandler>
     * @readonly
     */
    private $strategyHandlers;
    /**
     * @readonly
     * @var \Unleash\Client\Repository\UnleashRepository
     */
    private $repository;
    /**
     * @readonly
     * @var \Unleash\Client\Client\RegistrationService
     */
    private $registrationService;
    /**
     * @readonly
     * @var \Unleash\Client\Configuration\UnleashConfiguration
     */
    private $configuration;
    /**
     * @readonly
     * @var \Unleash\Client\Metrics\MetricsHandler
     */
    private $metricsHandler;
    /**
     * @readonly
     * @var \Unleash\Client\Variant\VariantHandler
     */
    private $variantHandler;
    /**
     * @param iterable<StrategyHandler> $strategyHandlers
     */
    public function __construct(iterable $strategyHandlers, UnleashRepository $repository, RegistrationService $registrationService, UnleashConfiguration $configuration, MetricsHandler $metricsHandler, VariantHandler $variantHandler)
    {
        $this->strategyHandlers = $strategyHandlers;
        $this->repository = $repository;
        $this->registrationService = $registrationService;
        $this->configuration = $configuration;
        $this->metricsHandler = $metricsHandler;
        $this->variantHandler = $variantHandler;
        if ($configuration->isAutoRegistrationEnabled()) {
            $this->register();
        }
    }
    public function isEnabled(string $featureName, ?Context $context = null, bool $default = false): bool
    {
        $context = $context ?? $this->configuration->getContextProvider()->getContext();

        $feature = $this->repository->findFeature($featureName);
        if ($feature === null) {
            $event = new FeatureToggleNotFoundEvent($context, $featureName);
            $this->configuration->getEventDispatcher()->dispatch($event, UnleashEvents::FEATURE_TOGGLE_NOT_FOUND);

            return $default;
        }

        if (method_exists($feature, 'hasImpressionData') && $feature->hasImpressionData()) {
            $event = new ImpressionDataEvent(ImpressionDataEventType::IS_ENABLED, Uuid::v4(), clone $this->configuration, clone $context, clone $feature, null);
            $this->configuration->getEventDispatcher()->dispatch($event, UnleashEvents::IMPRESSION_DATA);
        }

        if (!$feature->isEnabled()) {
            $event = new FeatureToggleDisabledEvent($feature, $context);
            $this->configuration->getEventDispatcher()->dispatch($event, UnleashEvents::FEATURE_TOGGLE_DISABLED);

            $this->metricsHandler->handleMetrics($feature, false);

            return false;
        }

        $strategies = $feature->getStrategies();
        if (!(is_array($strategies) || $strategies instanceof \Countable)) {
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
            $event = new FeatureToggleMissingStrategyHandlerEvent($context, $feature);
            $this->configuration->getEventDispatcher()->dispatch($event, UnleashEvents::FEATURE_TOGGLE_MISSING_STRATEGY_HANDLER);
        }

        $this->metricsHandler->handleMetrics($feature, false);

        return false;
    }

    public function getVariant(string $featureName, ?Context $context = null, ?Variant $fallbackVariant = null): Variant
    {
        $fallbackVariant = $fallbackVariant ?? $this->variantHandler->getDefaultVariant();
        $context = $context ?? $this->configuration->getContextProvider()->getContext();

        $feature = $this->repository->findFeature($featureName);
        if ($feature === null || !$feature->isEnabled() || !count($feature->getVariants())) {
            return $fallbackVariant;
        }

        $variant = $this->variantHandler->selectVariant($feature, $context);
        if ($variant !== null) {
            $this->metricsHandler->handleMetrics($feature, true, $variant);

            if (method_exists($feature, 'hasImpressionData') && $feature->hasImpressionData()) {
                $event = new ImpressionDataEvent(ImpressionDataEventType::GET_VARIANT, Uuid::v4(), clone $this->configuration, clone $context, clone $feature, clone $variant);
                $this->configuration->getEventDispatcher()->dispatch($event, UnleashEvents::IMPRESSION_DATA);
            }
        }

        return $variant ?? $fallbackVariant;
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
