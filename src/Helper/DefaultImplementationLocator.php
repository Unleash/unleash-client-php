<?php

namespace Unleash\Client\Helper;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use LogicException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * @internal
 */
final class DefaultImplementationLocator
{
    /**
     * @var array<string,string[]>
     */
    private $supportedPackages = [
        'client' => [
            'guzzlehttp/guzzle',
            'symfony/http-client',
        ],
        'factory' => [
            'guzzlehttp/guzzle',
            'symfony/http-client',
        ],
        'cache' => [
            'symfony/cache',
            'cache/filesystem-adapter',
        ],
    ];

    /**
     * @var array<string,array<string,array>>
     */
    private $defaultImplementations = [
        'client' => [
            Client::class => [],
            Psr18Client::class => [],
        ],
        'factory' => [
            HttpFactory::class => [],
            Psr18Client::class => [],
        ],
        'cache' => [
            FilesystemCachePool::class => [
                Filesystem::class => [
                    Local::class => ['{{tmpDir}}/unleash-default-cache'],
                ],
            ],
            Psr16Cache::class => [
                FilesystemAdapter::class => [
                    '',
                    0,
                    '{{tmpDir}}/unleash-default-cache',
                ],
            ],
        ],
    ];

    public function findHttpClient(): ?ClientInterface
    {
        foreach ($this->defaultImplementations['client'] as $class => $config) {
            if (class_exists($class)) {
                $result = $this->constructObject($class, $config);
                if (!$result instanceof ClientInterface) {
                    // @codeCoverageIgnoreStart
                    throw new LogicException('The resulting object is not an instance of ' . ClientInterface::class);
                    // @codeCoverageIgnoreEnd
                }

                return $result;
            }
        }

        return null;
    }

    public function findRequestFactory(): ?RequestFactoryInterface
    {
        foreach ($this->defaultImplementations['factory'] as $class => $config) {
            if (class_exists($class)) {
                $result = $this->constructObject($class, $config);
                if (!$result instanceof RequestFactoryInterface) {
                    // @codeCoverageIgnoreStart
                    throw new LogicException(
                        'The resulting object is not an instance of ' . RequestFactoryInterface::class
                    );
                    // @codeCoverageIgnoreEnd
                }

                return $result;
            }
        }

        return null;
    }

    public function findCache(): ?CacheInterface
    {
        foreach ($this->defaultImplementations['cache'] as $class => $parameters) {
            if (class_exists($class)) {
                $result = $this->constructObject($class, $parameters);
                if (!$result instanceof CacheInterface) {
                    // @codeCoverageIgnoreStart
                    throw new LogicException(
                        'The resulting object is not an instance of ' . CacheInterface::class
                    );
                    // @codeCoverageIgnoreEnd
                }

                return $result;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function getHttpClientPackages(): array
    {
        return $this->supportedPackages['client'];
    }

    /**
     * @return string[]
     */
    public function getRequestFactoryPackages(): array
    {
        return $this->supportedPackages['factory'];
    }

    /**
     * @return string[]
     */
    public function getCachePackages(): array
    {
        return $this->supportedPackages['cache'];
    }

    /**
     * @param class-string            $class
     * @param array<int|string,mixed> $parameters
     */
    private function constructObject(string $class, array $parameters): object
    {
        if (!class_exists($class)) {
            // @codeCoverageIgnoreStart
            throw new LogicException("The class '{$class}' does not exist");
            // @codeCoverageIgnoreEnd
        }

        $resolvedParameters = [];
        foreach ($parameters as $parameter => $value) {
            if (is_string($parameter)) {
                if (!class_exists($parameter)) {
                    // @codeCoverageIgnoreStart
                    throw new LogicException("Unsupported string parameter that is not a class: '{$parameter}'");
                    // @codeCoverageIgnoreEnd
                }
                if (!is_array($value)) {
                    // @codeCoverageIgnoreStart
                    throw new LogicException('Class arguments must be an array');
                    // @codeCoverageIgnoreEnd
                }
                $resolvedParameters[] = $this->constructObject($parameter, $value);
            } else {
                if (is_string($value) && strpos($value, '{{tmpDir}}') !== false) {
                    $value = str_replace('{{tmpDir}}', sys_get_temp_dir(), $value);
                }
                $resolvedParameters[] = $value;
            }
        }

        return new $class(...$resolvedParameters);
    }
}
