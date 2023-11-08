### Migration guide from version 1.x to 2.x

The 2.0 release most of all contains a cleanup of old deprecated functions, classes etc. If you're not using any of
the deprecated features (except for the deprecated strategies, those are still present in 2.x) and you're not implementing
any of our interfaces, you should be good to go without a single change!

Basically, you don't need to change anything if all the following is true:

- You were using the builder to create an `Unleash` object
- You either haven't implemented any of our interfaces, or you implemented even the non-mandatory methods
- You haven't been using any deperecated features (most notably setting a default context without using custom context provider)

## What's changed:

- Every class that could be made `readonly` was made `readonly` (this shouldn't really mean anything to you, all classes were
  already `final`, so you couldn't extend them anyway).
- All interfaces that had methods only typehinted in phpdoc now include proper declarations instead:
  - `\Unleash\Client\Configuration\Context`
  - `\Unleash\Client\DTO\Constraint`
  - `\Unleash\Client\DTO\Feature`
  - `\Unleash\Client\DTO\Strategy`
- Some previously not required properties of `UnleashConfiguration` object are now mandatory. If you were creating your
  `Unleash` instance using the `UnleashBuilder`, nothing changes for you.
- `symfony/event-dispatcher` is now a mandatory dependency
  - this includes removing the helper classes that wrapped the event dispatcher logic, like `\Unleash\Helper\EventDispatcher`
    and the stub Symfony files
- support for setting a default context has been removed, please use a custom context provider
  - this includes removing the `SettableUnleashContextProvider` interface
- `getEventDispatcher()` of `UnleashConfiguration` has its type changed from `\Unleash\Helper\EventDispatcher` to `EventDispatcherInterface`
- `DefaultProxyFeature` had all its properties changed to private, getters have been added and the class is now `JsonSerializable`
- `AbstractHttpClientTest` is renamed to `AbstractHttpClientTestCase`
- the stickiness calculator interface now has a 3rd optional parameter
