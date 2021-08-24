<?php

namespace Unleash\Client\ContextProvider;

use JetBrains\PhpStorm\Pure;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashContext;

final class DefaultUnleashContextProvider implements UnleashContextProvider, SettableUnleashContextProvider
{
    public function __construct(private ?Context $defaultContext = null)
    {
    }

    #[Pure]
    public function getContext(): Context
    {
        return $this->defaultContext ? clone $this->defaultContext : new UnleashContext();
    }

    /**
     * @todo remove in next major version
     *
     * @internal
     */
    public function setDefaultContext(Context $context): self
    {
        $this->defaultContext = $context;

        return $this;
    }
}
