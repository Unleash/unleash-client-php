<?php

namespace Unleash\Client\Tests\TestHelpers;

use JsonSerializable;
use Traversable;
use Unleash\Client\Bootstrap\BootstrapProvider;

final class CustomBootstrapProviderImpl80 implements BootstrapProvider
{
    public function getBootstrap(): array|JsonSerializable|Traversable|null
    {
        return null;
    }
}
