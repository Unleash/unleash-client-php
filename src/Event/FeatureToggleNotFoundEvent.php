<?php

namespace Unleash\Client\Event;

use Unleash\Client\Configuration\Context;

final class FeatureToggleNotFoundEvent extends AbstractEvent
{
    /**
     * @internal
     */
    public function __construct(
        private Context $context,
        private string $featureName,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getFeatureName(): string
    {
        return $this->featureName;
    }
}
