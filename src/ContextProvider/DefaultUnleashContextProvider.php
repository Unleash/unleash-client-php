<?php

namespace Unleash\Client\ContextProvider;

use JetBrains\PhpStorm\Pure;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashContext;

final class DefaultUnleashContextProvider implements UnleashContextProvider, SettableUnleashContextProvider
{
    /**
     * @var \Unleash\Client\Configuration\Context|null
     */
    private $defaultContext;
    public function __construct(?Context $defaultContext = null)
    {
        $this->defaultContext = $defaultContext;
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
     * @return $this
     */
    public function setDefaultContext(Context $context): \Unleash\Client\ContextProvider\SettableUnleashContextProvider
    {
        $this->defaultContext = $context;

        return $this;
    }
}
