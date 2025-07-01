<?php

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Unleash\Client\DTO\Feature;
use Unleash\Client\Repository\UnleashRepository;
use Unleash\Client\UnleashBuilder;

require __DIR__ . '/_common.php';

// first create a builder to later get the default repository
$unleashBuilder = UnleashBuilder::create()
    ->withAppName($appName)
    ->withAppUrl($appUrl)
    ->withInstanceId($instanceId)
    ->withHeader('Authorization', $apiKey)
;
$originalRepository = $unleashBuilder->buildRepository();
$cache = new FilesystemAdapter();

// create a decorator, in this example to cache every single feature separately
$decoratedRepository = new readonly class ($originalRepository, $cache) implements UnleashRepository {
    public function __construct(
        private UnleashRepository $original,
        private CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * Cache the item separately to avoid fetching the whole feature tree every time
     */
    public function findFeature(string $featureName): ?Feature
    {
        $cacheItem = $this->cache->getItem($featureName);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $feature = $this->original->findFeature($featureName);
        $cacheItem->set($feature);
        $cacheItem->expiresAfter(new DateInterval('PT10M'));
        $this->cache->save($cacheItem);

        return $feature;
    }

    public function getFeatures(): iterable
    {
        return $this->original->getFeatures();
    }

    public function refreshCache(): void
    {
        $this->original->refreshCache();
    }
};

$unleash = $unleashBuilder
    ->withRepository($decoratedRepository)
    ->build();

if ($unleash->isEnabled('some-feature')) {
    // todo
}
