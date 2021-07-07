<?php

namespace Rikudou\Tests\Unleash\Traits;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Psr\SimpleCache\CacheInterface;

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
            $this->_cache = new FilesystemCachePool(
                new Filesystem(
                    new Local(sys_get_temp_dir() . '/unleash-sdk-tests')
                )
            );
        }

        return $this->_cache;
    }
}
