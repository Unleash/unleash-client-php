<?php

namespace Unleash\Client\Enum;

/**
 * @internal
 */
final class CacheKey
{
    public const string METRICS_BUCKET = 'unleash.client.metrics.bucket';

    public const string FEATURES = 'unleash.client.feature.list';

    public const string REGISTRATION = 'unleash.client.metrics.registration';

    public const string FEATURES_RESPONSE = 'unleash.client.feature.response';
}
