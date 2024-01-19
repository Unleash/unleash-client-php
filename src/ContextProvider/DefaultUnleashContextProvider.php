<?php

namespace Unleash\Client\ContextProvider;

use JetBrains\PhpStorm\Pure;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashContext;

final class DefaultUnleashContextProvider implements UnleashContextProvider
{
    #[Pure]
    public function getContext(): Context
    {
        return new UnleashContext();
    }
}
