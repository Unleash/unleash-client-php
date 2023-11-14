<?php

namespace Unleash\Client\Bootstrap;

use JsonSerializable;
use Traversable;

final class EmptyBootstrapProvider implements BootstrapProvider
{
    /**
     * @return mixed[]|\JsonSerializable|\Traversable|null
     */
    public function getBootstrap()
    {
        return null;
    }
}
