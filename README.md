[![Tests](https://github.com/RikudouSage/UnleashSDK/actions/workflows/tests.yaml/badge.svg)](https://github.com/RikudouSage/UnleashSDK/actions/workflows/tests.yaml)
[![Tests (7.x)](https://github.com/RikudouSage/UnleashSDK/actions/workflows/tests-7.x.yaml/badge.svg)](https://github.com/RikudouSage/UnleashSDK/actions/workflows/tests-7.x.yaml)
[![Coverage Status](https://img.shields.io/coveralls/github/RikudouSage/UnleashSDK?label=Code%20Coverage)](https://coveralls.io/github/RikudouSage/UnleashSDK?branch=master)

A PHP implementation of the [Unleash protocol](https://www.getunleash.io/)
aka [Feature Flags](https://docs.gitlab.com/ee/operations/feature_flags.html) in GitLab.

This implementation conforms to the official Unleash standards except for [missing features](#missing-features).

> Unleash allows you to gradually release your app's feature before doing a full release based on multiple strategies
> like releasing to only specific users or releasing to a percentage of your user base. Read more in the above linked
> documentations.

## Installation

`composer require rikudou/unleash-sdk`

Requires PHP 7.3 or newer.

> You will also need some implementation of [PSR-18](https://packagist.org/providers/psr/http-client-implementation)
> and [PSR-17](https://packagist.org/providers/psr/http-factory-implementation), for example 
> [Guzzle](https://packagist.org/packages/guzzlehttp/guzzle)

## Usage

The basic usage is getting the `Unleash` object and checking for a feature:

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

If you're using Unleash v4 you also need to specify authorization key (API key), you can do so with custom header.

```php
<?php

use Rikudou\Unleash\UnleashBuilder;

$builder = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id')
    ->withHeader('Authorization', 'my-api-key');
```

#### Optional parameters

Some optional parameters can be set, these include:

- http client implementation
- request factory implementation
- cache implementation ([PSR-16](https://packagist.org/providers/psr/simple-cache-implementation))
- cache ttl
- available strategies
- http headers

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
    )
    // add headers one by one, if you specify a header with the same name multiple times it will be replaced by the
    // latest value
    ->withHeader('My-Custom-Header', 'some-value')
    ->withHeader('Some-Other-Header', 'some-other-value')
    // you can specify multiple headers at the same time, be aware that this REPLACES all the headers
    ->withHeaders([
        'Yet-Another-Header' => 'and-another-value',
    ]);
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

// assume the feature is set to use the user id, the first call returns false (no context given), the second
// one returns true/false based on the user id
$unleash->isEnabled('some-feature');
$context = new UnleashContext(currentUserId: 'some-user-id');
$unleash->isEnabled('some-feature', $context);

// the same goes for session, assume the session isn't started yet and the feature is set to use the session type
$unleash->isEnabled('some-feature'); // returns false because no session is available

$context = new UnleashContext(sessionId: 'some-session-id');
$unleash->isEnabled('some-feature', $context); // works because you provided the session id manually

session_start();
$unleash->isEnabled('some-feature'); // works because the session is started

// lastly you can force the feature to use the random type which always works
$unleash->isEnabled('some-feature');
```

## Variants

You can use multiple variants of one feature, for example for A/B testing. If no variant matches or the feature doesn't
have any variants, a default one will be returned which returns `false` for `isEnabled()`. You can also provide your
own default variant.

Variant may or may not contain a payload.

```php
<?php

use Rikudou\Unleash\DTO\DefaultVariant;
use Rikudou\Unleash\UnleashBuilder;
use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\Enum\VariantPayloadType;
use Rikudou\Unleash\DTO\DefaultVariantPayload;

$unleash = UnleashBuilder::create()
    ->withAppName('Some app name')
    ->withAppUrl('https://some-app-url.com')
    ->withInstanceId('Some instance id')
    ->build();
    
$variant = $unleash->getVariant('nonexistentFeature');
assert($variant->isEnabled() === false);

// getVariant() does isEnabled() call in the background meaning that it will return the default falsy variant
// whenever isEnabled() returns false
$variant = $unleash->getVariant('existingFeatureThatThisUserDoesNotHaveAccessTo');
assert($variant->isEnabled() === false);

$variant = $unleash->getVariant('someFeature', new UnleashContext(currentUserId: '123'));
if ($variant->isEnabled()) {
    $payload = $variant->getPayload();
    if ($payload !== null) {
        if ($payload->getType() === VariantPayloadType::JSON) {
            $jsonData = $payload->fromJson();
        }
        $stringPayload = $payload->getValue();
    }
}

// providing custom default variant

$variant = $unleash->getVariant('nonexistentFeature', fallbackVariant: new DefaultVariant(
    'variantName',
    enabled: true,
    payload: new DefaultVariantPayload(VariantPayloadType::STRING, 'somePayload'),
));
assert($variant->getPayload()->getValue() === 'somePayload');
```

## Client registration

By default, the library automatically registers itself as an application in the Unleash server. If you want to prevent
this, use `withAutomaticRegistrationEnabled(false)` in the builder.

```php
<?php

use Rikudou\Unleash\UnleashBuilder;

$unleash = UnleashBuilder::create()
    ->withAppName('Some App Name')
    ->withAppUrl('https://somewhere.com')
    ->withInstanceId('some-instance-id')
    ->withAutomaticRegistrationEnabled(false)
    ->build();

// event though the client will not attempt to register, you can still use isEnabled()
$unleash->isEnabled('someFeature');

// if you want to register manually
$unleash->register();

// you can call the register method multiple times, the Unleash server doesn't mind
$unleash->register();
$unleash->register();
```

## Metrics

By default, this library sends metrics which are simple statistics about whether user was granted access or not.

> Warning: If you don't provide a cache implementation, there will be additional http call with metrics for every
> `isEnabled()` call.

If you use cache the metrics will be bundled and sent once the bundle created time crosses the configured threshold.
By default this threshold is 30,000 milliseconds (30 seconds) meaning that when a new bundle gets created it won't be
sent sooner than in 30 seconds. That doesn't mean it's guaranteed that the metrics will be sent every 30 seconds, it
only guarantees that the metrics won't be sent sooner.

Example:

1. user visits your site and this sdk gets triggered, no metric has been sent
2. after five seconds user visits another page where again this sdk gets triggered, no metric sent
3. user waits one minute before doing anything, no one else is accessing your site
4. after one minute user visits another page, the metrics have been sent to the Unleash server

In the example above the metric bundle gets sent after 1 minute and 5 seconds because there was no one to trigger
the code.

```php
<?php

use Rikudou\Unleash\UnleashBuilder;

$unleash = UnleashBuilder::create()
    ->withAppName('Some App Name')
    ->withAppUrl('https://somewhere.com')
    ->withInstanceId('some-instance-id')
    ->withMetricsEnabled(false) // turn off metric sending
    ->withMetricsEnabled(true) // turn on metric sending
    ->withMetricsInterval(10_000) // interval in milliseconds (10 seconds)
    ->build();

// the metric will be collected but not sent immediately
$unleash->isEnabled('test');
sleep(10);
// now the metrics will get sent
$unleash->isEnabled('test');
```

## GitLab specifics

- In GitLab you have to use the provided instance id, you cannot create your own.
- No authorization header is necessary.
- Instead of app name you need to specify the GitLab environment.
  - For this purpose you can use `withGitlabEnvironment()` method in builder, it's an alias to `withAppName()` but
    communicates the intent better.
- GitLab doesn't use registration system, you can set the SDK to disable automatic registration and save one http call.
- GitLab doesn't read metrics, you can set the SDK to disable sending them and save some http calls.

```php
<?php

use Rikudou\Unleash\UnleashBuilder;

$gitlabUnleash = UnleashBuilder::create()
    ->withInstanceId('H9sU9yVHVAiWFiLsH2Mo') // generated in GitLab
    ->withAppUrl('https://git.example.com/api/v4/feature_flags/unleash/1')
    ->withGitlabEnvironment('Production')
    ->withAutomaticRegistrationEnabled(false)
    ->withMetricsEnabled(false)
    ->build();
```

## Missing features

Not every feature has been implemented yet, currently missing features are:

- constraints
- stickiness based on custom fields
