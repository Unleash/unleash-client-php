# Event system in Unleash PHP SDK

This SDK supports events using [`symfony/event-dispatcher`](https://packagist.org/packages/symfony/event-dispatcher).

## Installation

The event dispatcher is not a mandatory component of this SDK, so you need to install it by running:

`composer require symfony/event-dispatcher`

## Usage

You can add event subscribers to the builder object:

```php
<?php

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Unleash\Client\Event\FeatureToggleDisabledEvent;
use Unleash\Client\Event\FeatureToggleNoStrategyHandlerEvent;
use Unleash\Client\Event\FeatureToggleNotFoundEvent;
use Unleash\Client\Event\FeatureVariantBeforeFallbackReturnedEvent;
use Unleash\Client\UnleashBuilder;
use Unleash\Client\Event\UnleashEvents;

class MyEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            UnleashEvents::FEATURE_TOGGLE_DISABLED => 'onFeatureDisabled',
            UnleashEvents::FEATURE_TOGGLE_NO_STRATEGY_HANDLER => 'onNoStrategyHandler',
            UnleashEvents::FEATURE_TOGGLE_NOT_FOUND => 'onFeatureNotFound',
            UnleashEvents::FEATURE_VARIANT_BEFORE_FALLBACK_RETURNED => 'onVariantNotFound',
        ];
    }
    
    public function onFeatureDisabled(FeatureToggleDisabledEvent $event)
    {
        // todo
    }
    
    public function onNoStrategyHandler(FeatureToggleNoStrategyHandlerEvent $event)
    {
        // todo
    }
    
    public function onFeatureNotFound(FeatureToggleNotFoundEvent $event)
    {
        // todo
    }
    
    public function onVariantNotFound(FeatureVariantBeforeFallbackReturnedEvent $event)
    {
        // todo
    }
}

$unleash = UnleashBuilder::create()
    ->withAppName('My App')
    ->withAppUrl('http://localhost:4242')
    ->withInstanceId('test')
    ->withEventSubscriber(new MyEventSubscriber())
    ->build();

$unleash->isEnabled('test');
```

The relevant methods will be called in the above example when their respective event occurs.

### List of events:

- `\Unleash\Client\Event\UnleashEvents::FEATURE_TOGGLE_NOT_FOUND` - when a feature with the name isn't found on the
  unleash server (or in the bootstrap if it's used). Event object: `Unleash\Client\Event\FeatureToggleNotFoundEvent`
- `\Unleash\Client\Event\UnleashEvents::FEATURE_TOGGLE_DISABLED` - when a feature is found but it's disabled.
  Event object: Unleash\Client\Event\FeatureToggleDisabledEvent
- `\Unleash\Client\Event\UnleashEvents::FEATURE_TOGGLE_NO_STRATEGY_HANDLER` - when there is no suitable strategy handler
  implemented for any of the feature's strategies. Event object: `Unleash\Client\Event\FeatureToggleNoStrategyHandlerEvent`
- `\Unleash\Client\Event\UnleashEvents::FEATURE_VARIANT_BEFORE_FALLBACK_RETURNED` - triggered before the fallback variant
  would be returned in the `getVariant()` call. Event object: `Unleash\Client\Event\FeatureVariantBeforeFallbackReturnedEvent`

## FEATURE_TOGGLE_NOT_FOUND event

You can set a feature that will be evaluated further or set a default value that will be returned.

Example:

```php
<?php

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Unleash\Client\Event\FeatureToggleNotFoundEvent;
use Unleash\Client\Event\UnleashEvents;
use Unleash\Client\DTO\DefaultFeature;
use Unleash\Client\UnleashBuilder;

class MyEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            UnleashEvents::FEATURE_TOGGLE_NOT_FOUND => 'onNotFound',
        ];
    }
    
    public function onNotFound(FeatureToggleNotFoundEvent $event): void
    {
        if ($event->getFeatureName() === 'someFeature') {
            // the call to Unleash::isEnabled() will return true
            $event->setEnabled(true);
        }
        
        if ($event->getFeatureName() === 'someOtherFeature') {
            $feature = new DefaultFeature(name: 'someExistingFeature', enabled: true);
            // the call to Unleash::isEnabled() will continue as if this feature was found
            $event->setFeature($feature);
        }
    }
}

$unleash = UnleashBuilder::create()
    ->withEventSubscriber(new MyEventSubscriber())
    ->build();

$unleash->isEnabled('someFeature'); // true
$unleash->isEnabled('someOtherFeature'); // true, assuming the strategies for 'someExistingFeature' evaluate to true
```