<?php

namespace Unleash\Client\Helper\Builder;

use Psr\SimpleCache\CacheInterface;

interface CacheAware
{
    public function setCache(CacheInterface $cache): void;
}
