<?php

namespace Unleash\Client\Helper\Builder;

use Psr\SimpleCache\CacheInterface;

interface StaleCacheAware
{
    public function setStaleCache(CacheInterface $cache): void;
}
