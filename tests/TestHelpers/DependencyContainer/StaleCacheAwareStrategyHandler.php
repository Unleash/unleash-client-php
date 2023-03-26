<?php

namespace Unleash\Client\Tests\TestHelpers\DependencyContainer;

use Psr\SimpleCache\CacheInterface;
use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Strategy;
use Unleash\Client\Helper\Builder\StaleCacheAware;
use Unleash\Client\Strategy\StrategyHandler;

final class StaleCacheAwareStrategyHandler implements StrategyHandler, StaleCacheAware
{
    public ?CacheInterface $cache = null;

    public function setStaleCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    public function supports(Strategy $strategy): bool
    {
        return false;
    }

    public function getStrategyName(): string
    {
        return '';
    }

    public function isEnabled(Strategy $strategy, Context $context): bool
    {
        return false;
    }
}
