<?php

namespace Unleash\Client\Bootstrap;

use JsonSerializable;
use Traversable;

interface BootstrapProvider
{
    /**
     * @return mixed[]|\JsonSerializable|\Traversable|null
     */
    public function getBootstrap();
}
