<?php

namespace Unleash\Client\Tests\Traits;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Psr16Cache;

trait FakeCacheImplementationTrait
{
    private function getCache(): CacheInterface
    {
        return new Psr16Cache(new NullAdapter());
    }
}
