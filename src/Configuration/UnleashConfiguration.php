<?php

namespace Rikudou\Unleash\Configuration;

use LogicException;
use Psr\SimpleCache\CacheInterface;

final class UnleashConfiguration
{
    /**
     * @param array<string,string> $headers
     */
    public function __construct(
        private string $url,
        private string $appName,
        private string $instanceId,
        private ?CacheInterface $cache = null,
        private int $ttl = 30,
        private int $metricsInterval = 30_000,
        private bool $metricsEnabled = true,
        private array $headers = [],
        private bool $autoRegistrationEnabled = true,
    ) {
    }

    public function getCache(): CacheInterface
    {
        if ($this->cache === null) {
            throw new LogicException('Cache handler is not set');
        }

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

    public function setCache(CacheInterface $cache): self
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

    public function setMetricsInterval(int $metricsInterval): self
    {
        $this->metricsInterval = $metricsInterval;

        return $this;
    }

    public function setMetricsEnabled(bool $metricsEnabled): self
    {
        $this->metricsEnabled = $metricsEnabled;

        return $this;
    }

    /**
     * @return array<string,string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array<string,string> $headers
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function isAutoRegistrationEnabled(): bool
    {
        return $this->autoRegistrationEnabled;
    }

    public function setAutoRegistrationEnabled(bool $autoRegistrationEnabled): self
    {
        $this->autoRegistrationEnabled = $autoRegistrationEnabled;

        return $this;
    }
}
