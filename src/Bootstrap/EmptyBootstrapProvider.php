<?php

namespace Unleash\Client\Bootstrap;

use JsonSerializable;
use Traversable;

final class EmptyBootstrapProvider implements BootstrapProvider
{
    public function getBootstrap(): array|JsonSerializable|Traversable|null
    {
        return null;
    }
}
