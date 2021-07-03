<?php

namespace Rikudou\Unleash\Configuration;

use Psr\SimpleCache\CacheInterface;

final class UnleashConfiguration
{
    private ?CacheInterface $cache = null;

    private int $ttl = 30;

    private int $metricsInterval = 30_000;

    private bool $metricsEnabled = true;

    public function __construct(
        private string $url,
        private string $appName,
        private string $instanceId
    ) {
    }

    public function getCache(): ?CacheInterface
    {
        return $this->cache;
    }

    public function getUrl(): string
    {
        $url = $this->url;
        if (substr($url, -1) !== '/') {
            $url .= '/';
        }

        return $url;
    }

    public function getAppName(): string
    {
        return $this->appName;
    }

    public function getInstanceId(): string
    {
        return $this->instanceId;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function setCache(?CacheInterface $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    public function setTtl(int $ttl): self
    {
        $this->ttl = $ttl;

        return $this;
    }

    public function getMetricsInterval(): int
    {
        return $this->metricsInterval;
    }

    public function isMetricsEnabled(): bool
    {
        return $this->metricsEnabled;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setAppName(string $appName): self
    {
        $this->appName = $appName;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setInstanceId(string $instanceId): self
    {
        $this->instanceId = $instanceId;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setMetricsInterval(int $metricsInterval): self
    {
        $this->metricsInterval = $metricsInterval;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setMetricsEnabled(bool $metricsEnabled): self
    {
        $this->metricsEnabled = $metricsEnabled;

        return $this;
    }
}
