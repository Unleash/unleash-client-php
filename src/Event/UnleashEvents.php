<?php

namespace Unleash\Client\Event;

final class UnleashEvents
{
    /**
     * When a feature toggle is not found this event gets triggered.
     *
     * You can set your own feature using ->setFeature() on the event object
     * or change the default response using ->setEnabled().
     *
     * @Event("Unleash\Client\Event\FeatureToggleNotFoundEvent")
     */
    public const FEATURE_TOGGLE_NOT_FOUND = 'unleash.event.toggle.not_found';

    /**
     * Triggered when a feature toggle is disabled, allows you to alter the feature object
     * to for example change the feature toggle to enabled.
     *
     * @Event("Unleash\Client\Event\FeatureToggleDisabledEvent")
     */
    public const FEATURE_TOGGLE_DISABLED = 'unleash.event.toggle.disabled';

    /**
     * Triggered when no strategy handler has been found for the feature.
     * Allows you to explicitly set a strategy handler that will be used regardless of actual support
     * for the strategy.
     *
     * @Event("Unleash\Client\Event\FeatureToggleMissingStrategyHandlerEvent")
     */
    public const FEATURE_TOGGLE_MISSING_STRATEGY_HANDLER = 'unleash.event.toggle.missing_strategy_handler';

    /**
     * Triggered before a fallback variant would be returned. Allows you to set a different variant.
     *
     * @Event("Unleash\Client\Event\FeatureVariantBeforeFallbackReturnedEvent")
     */
    public const FEATURE_VARIANT_BEFORE_FALLBACK_RETURNED = 'unleash.event.variant.before_fallback_returned';
}
