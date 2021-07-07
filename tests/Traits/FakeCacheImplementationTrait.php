<?php

namespace Rikudou\Tests\Unleash\Traits;

use Psr\SimpleCache\CacheInterface;

trait FakeCacheImplementationTrait
{
    private function getCache(): CacheInterface
    {
        return new class implements CacheInterface {
            public function get($key, $default = null)
            {
                return $default;
            }

            public function set($key, $value, $ttl = null): bool
            {
                return true;
            }

            public function delete($key): bool
            {
                return true;
            }

            public function clear(): bool
            {
                return true;
            }

            public function getMultiple($keys, $default = null)
            {
                return $default;
            }

            public function setMultiple($values, $ttl = null): bool
            {
                return true;
            }

            public function deleteMultiple($keys): bool
            {
                return true;
            }

            public function has($key): bool
            {
                return false;
            }
        };
    }
}
