<?php

namespace Unleash\Client\Event;

use Unleash\Client\Configuration\Context;

final class FeatureToggleNotFoundEvent extends AbstractEvent
{
    /**
     * @readonly
     */
    private Context $context;
    /**
     * @readonly
     */
    private string $featureName;
    /**
     * @internal
     */
    public function __construct(Context $context, string $featureName)
    {
        $this->context = $context;
        $this->featureName = $featureName;
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
