<?php

namespace Unleash\Client\ContextProvider;

use JetBrains\PhpStorm\Deprecated;
use Unleash\Client\Configuration\Context;

/**
 * @todo Remove in next major version
 */
#[Deprecated('This interface will be removed in next major version')]
interface SettableUnleashContextProvider extends UnleashContextProvider
{
    public function setDefaultContext(Context $context): self;
}
