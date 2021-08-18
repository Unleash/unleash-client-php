<?php

namespace Unleash\Client\Configuration;

use JetBrains\PhpStorm\Pure;
use LogicException;
use Psr\SimpleCache\CacheInterface;

final class UnleashConfiguration
{
    /**
     * @var string
     */
    private $url;
    /**
     * @var string
     */
    private $appName;
    /**
     * @var string
     */
    private $instanceId;
    /**
     * @var \Psr\SimpleCache\CacheInterface|null
     */
    private $cache;
    /**
     * @var int
     */
    private $ttl = 30;
    /**
     * @var int
     */
    private $metricsInterval = 30000;
    /**
     * @var bool
     */
    private $metricsEnabled = true;
    /**
     * @var mixed[]
     */
    private $headers = [];
    /**
     * @var bool
     */
    private $autoRegistrationEnabled = true;
    /**
     * @var \Unleash\Client\Configuration\Context|null
     */
    private $defaultContext;
    /**
     * @param array<string,string> $headers
     */
    public function __construct(string $url, string $appName, string $instanceId, ?CacheInterface $cache = null, int $ttl = 30, int $metricsInterval = 30000, bool $metricsEnabled = true, array $headers = [], bool $autoRegistrationEnabled = true, ?Context $defaultContext = null)
    {
        $this->url = $url;
        $this->appName = $appName;
        $this->instanceId = $instanceId;
        $this->cache = $cache;
        $this->ttl = $ttl;
        $this->metricsInterval = $metricsInterval;
        $this->metricsEnabled = $metricsEnabled;
        $this->headers = $headers;
        $this->autoRegistrationEnabled = $autoRegistrationEnabled;
        $this->defaultContext = $defaultContext;
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
        if (substr_compare($url, '/', -strlen('/')) !== 0) {
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

    /**
     * @return $this
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @return $this
     */
    public function setTtl(int $ttl)
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

    /**
     * @return $this
     */
    public function setUrl(string $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return $this
     */
    public function setAppName(string $appName)
    {
        $this->appName = $appName;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return $this
     */
    public function setInstanceId(string $instanceId)
    {
        $this->instanceId = $instanceId;

        return $this;
    }

    /**
     * @return $this
     */
    public function setMetricsInterval(int $metricsInterval)
    {
        $this->metricsInterval = $metricsInterval;

        return $this;
    }

    /**
     * @return $this
     */
    public function setMetricsEnabled(bool $metricsEnabled)
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
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    public function isAutoRegistrationEnabled(): bool
    {
        return $this->autoRegistrationEnabled;
    }

    /**
     * @return $this
     */
    public function setAutoRegistrationEnabled(bool $autoRegistrationEnabled)
    {
        $this->autoRegistrationEnabled = $autoRegistrationEnabled;

        return $this;
    }

    #[Pure]
    public function getDefaultContext(): Context
    {
        return $this->defaultContext ?? new UnleashContext();
    }

    /**
     * @return $this
     */
    public function setDefaultContext(?Context $defaultContext)
    {
        $this->defaultContext = $defaultContext;

        return $this;
    }
}
