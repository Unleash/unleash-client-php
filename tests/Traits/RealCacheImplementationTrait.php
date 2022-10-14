<?php

namespace Unleash\Client\Tests\Traits;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

trait RealCacheImplementationTrait
{
    /**
     * @var CacheInterface|null
     */
    private $_cache;

    protected function tearDown(): void
    {
        if ($this->_cache !== null) {
            $this->_cache->clear();
        }
    }

    private function getCache(): CacheInterface
    {
        if ($this->_cache === null) {
            $this->_cache = new Psr16Cache(new ArrayAdapter());
        }

        return $this->_cache;
    }
}
