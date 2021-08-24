<?php

namespace Unleash\Client\ContextProvider;

use JetBrains\PhpStorm\Pure;
use Unleash\Client\Configuration\Context;

interface UnleashContextProvider
{
    #[Pure]
    public function getContext(): Context;
}
