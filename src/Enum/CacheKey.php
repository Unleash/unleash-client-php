<?php

namespace Unleash\Client\Enum;

/**
 * @internal
 */
final class CacheKey
{
    public const METRICS_BUCKET = 'unleash.client.metrics.bucket';

    public const FEATURES = 'unleash.client.feature.list';

    public const REGISTRATION = 'unleash.client.metrics.registration';

    public const FEATURES_RESPONSE = 'unleash.client.feature.response';
}
