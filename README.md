[![Tests](https://github.com/RikudouSage/UnleashSDK/actions/workflows/tests.yaml/badge.svg)](https://github.com/RikudouSage/UnleashSDK/actions/workflows/tests.yaml)

A PHP implementation of the [Unleash protocol](https://www.getunleash.io/)
aka [Feature Flags](https://docs.gitlab.com/ee/operations/feature_flags.html) in GitLab.

## Installation

`composer require rikudou/unleash-sdk`

> You will also need some implementation of [PSR-7](https://packagist.org/providers/psr/http-message-implementation)
> and [PSR-17](https://packagist.org/providers/psr/http-factory-implementation), for example 
> [Guzzle](https://packagist.org/packages/guzzlehttp/guzzle)

## Usage

The basic using is getting the `Unleash` object and checking for a feature:

```php
<?php

use Rikudou\Unleash\UnleashBuilder;

$unleash = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id')
    ->build();

if ($unleash->isEnabled('some-feature-name')) {
    // do something
}
```

You can (and in some cases you must) also provide a context object. If the feature doesn't exist on the server
you will get `false` from `isEnabled()`, but you can change the default value to `true`.

```php
<?php

use Rikudou\Unleash\UnleashBuilder;
use Rikudou\Unleash\Configuration\UnleashContext;

$unleash = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id')
    ->build();

$context = new UnleashContext(
    currentUserId: 'some-user-id-from-app',
    ipAddress: '127.0.0.1', // will be populated automatically from $_SERVER if needed
    sessionId: 'sess-123456', // will be populated automatically via session_id() if needed
);

// or using pre php 8 style:

$context = (new UnleashContext())
    ->setCurrentUserId('some-user-id-from-app')
    ->setIpAddress('127.0.0.1')
    ->setSessionId('sess-123456');

if ($unleash->isEnabled('some-feature', $context)) {
    // do something
}

// changing the default value for non-existent features
if ($unleash->isEnabled('nonexistent-feature', $context, true)) {
    // do something
}
```

### Builder

The builder contains many configuration options, and it's advised to always use the builder to construct an Unleash
instance. The builder is immutable.

The builder object can be created using the `create()` static method or by using its constructor:

```php
<?php

use Rikudou\Unleash\UnleashBuilder;

// both are the same
$builder = new UnleashBuilder();
$builder = UnleashBuilder::create();
```

#### Required parameters

The app name, instance id and app url are required as per the specification.

```php
<?php

use Rikudou\Unleash\UnleashBuilder;

$builder = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id');
```

#### Optional parameters

Some optional parameters can be set, these include:

- http client implementation
- request factory implementation
- cache implementation ([PSR-16](https://packagist.org/providers/psr/simple-cache-implementation))
- cache ttl
- available strategies

If you use Guzzle as your http implementation, the http client and request factory will be created automatically,
if you use any other implementation you must provide http client and request factory implementation on your own.

```php
<?php

use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Rikudou\Unleash\Stickiness\MurmurHashCalculator;
use Rikudou\Unleash\Strategy\DefaultStrategyHandler;
use Rikudou\Unleash\Strategy\GradualRolloutStrategyHandler;
use Rikudou\Unleash\Strategy\IpAddressStrategyHandler;
use Rikudou\Unleash\Strategy\UserIdStrategyHandler;
use Rikudou\Unleash\UnleashBuilder;

$builder = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id')
    // now the optional ones
    ->withHttpClient(new Client())
    ->withRequestFactory(new HttpFactory())
    ->withCacheHandler(new FilesystemCachePool( // example with using cache/filesystem-adapter
        new Filesystem(
            new Local(sys_get_temp_dir()),
        ),
    ), 30) // the second parameter is time to live in seconds
    ->withCacheTimeToLive(60) // you can also set the cache time to live separately
    // if you don't add any strategies, by default all strategies are added
    ->withStrategies( // this example includes all available strategies
        new DefaultStrategyHandler(),
        new GradualRolloutStrategyHandler(new MurmurHashCalculator()),
        new IpAddressStrategyHandler(),
        new UserIdStrategyHandler(),
    );
```

## Caching

It may be unnecessary to perform a http request every time you check if a feature is enabled, especially in popular
apps. That's why this library has built-in support for PSR-16 cache implementations.

If you don't provide any implementation no cache is used. You can also provide a TTL which defaults to 30 seconds.

```php
<?php

use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Rikudou\Unleash\UnleashBuilder;

$builder = UnleashBuilder::create()
    ->withCacheHandler(new FilesystemCachePool( // example with using cache/filesystem-adapter
        new Filesystem(
            new Local(sys_get_temp_dir()),
        ),
    ))
    ->withCacheTimeToLive(120);

// you can set the cache handler explicitly to null to disable cache

$builder = $builder
    ->withCacheHandler(null);
```

## Strategies

Unleash servers can use multiple strategies for enabling or disabling features. Which strategy gets used is defined
on the server. This implementation supports all non-deprecated v4 strategies except hostnames. 
[More here](https://docs.getunleash.io/user_guide/activation_strategy).

### Default strategy

This is the simplest of them and simply always returns true if the feature defines default as its chosen strategy
and doesn't need any context parameters.

### IP address strategy

Enables feature based on the IP address. Takes current user's IP address from the context object. You can provide your
own IP address or use the default (`$_SERVER['REMOTE_ADDR']`). Providing your own is especially useful if you're behind
proxy and thus `REMOTE_ADDR` would return your proxy server's IP address instead.

```php
<?php

use Rikudou\Unleash\UnleashBuilder;
use Rikudou\Unleash\Configuration\UnleashContext;

$unleash = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id')
    ->build();

// without context, using the auto detected IP
$enabled = $unleash->isEnabled('some-feature');

// with context
$context = new UnleashContext(ipAddress: $_SERVER['HTTP_X_FORWARDED_FOR']);
// or pre php 8 style
$context = (new UnleashContext())
    ->setIpAddress($_SERVER['HTTP_X_FORWARDED_FOR']);
$enabled = $unleash->isEnabled('some-feature', $context);
```

### User ID strategy

Enables feature based on the user ID. The user ID can be any string. You must always provide your own user id via 
context.

```php
<?php

use Rikudou\Unleash\UnleashBuilder;
use Rikudou\Unleash\Configuration\UnleashContext;

$unleash = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id')
    ->build();

$context = new UnleashContext(currentUserId: 'some-user-id');
$enabled = $unleash->isEnabled('some-feature', $context);
```

### Gradual rollout strategy

Also known as flexible rollout. Allows you to enable feature for only a percentage of users based on their user id,
session id or randomly. The default is to try in this order: user id, session id, random.

If you specify the user id type on your Unleash server, you must also provide the user id via context, same as in the
User ID strategy. Session ID can also be provided via context, it defaults to the current session id via `session_id()`
call.

> This strategy requires a stickiness calculator that transforms the id (user, session or random) into a number between
> 1 and 100. You can provide your own or use the default \Rikudou\Unleash\Stickiness\MurmurHashCalculator

```php
<?php

use Rikudou\Unleash\UnleashBuilder;
use Rikudou\Unleash\Configuration\UnleashContext;

$unleash = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id')
    ->build();

// assume the feature uses the default type which means that it will default to either session id (if session is started)
// or randomly
$unleash->isEnabled('some-feature');

// still using the default strategy but this time with user id (which is the first to be used if present)
$context = new UnleashContext(currentUserId: 'some-user-id');
$unleash->isEnabled('some-feature', $context);

// let's start the session to ensure the session id is used
session_start();
$unleash->isEnabled('some-feature');

// or you can provide your own session id
$context = new UnleashContext(sessionId: 'sess-123456');
$unleash->isEnabled('some-feature', $context);

// assume the feature is set to use the user id, the first call throws an exception (no context given), the second
// one works
$unleash->isEnabled('some-feature');
$context = new UnleashContext(currentUserId: 'some-user-id');
$unleash->isEnabled('some-feature', $context);

// the same goes for session, assume the session isn't started yet and the feature is set to use the session type
$unleash->isEnabled('some-feature'); // throws exception because no session is available

$context = new UnleashContext(sessionId: 'some-session-id');
$unleash->isEnabled('some-feature', $context); // works because you provided the session id manually

session_start();
$unleash->isEnabled('some-feature'); // works because the session is started

// lastly you can force the feature to use the random type which always works
$unleash->isEnabled('some-feature');
```

## Caveats

The GitLab's "Percent of users" (in Unleash documentation as "gradualRolloutUserId") is deprecated in Unleash v4 and
thus is not available in this library. It can be replaced on GitLab side with "Percent rollout" ("Gradual rollout" in
this library) and setting the "Based on" to "User ID".
