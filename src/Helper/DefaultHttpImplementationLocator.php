<?php

namespace Rikudou\Unleash\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\HttpClient\Psr18Client;

final class DefaultHttpImplementationLocator
{
    /**
     * @var array<string,string[]>
     */
    private array $supportedPackages = [
        'client' => [
            'guzzlehttp/guzzle',
            'symfony/http-client',
        ],
        'factory' => [
            'guzzlehttp/guzzle',
            'symfony/http-client',
        ],
    ];

    /**
     * @var array<string,array<string,array>>
     */
    private array $defaultImplementations = [
        'client' => [
            Client::class => [],
            Psr18Client::class => [],
        ],
        'factory' => [
            HttpFactory::class => [],
            Psr18Client::class => [],
        ],
    ];

    public function findHttpClient(): ?ClientInterface
    {
        foreach ($this->defaultImplementations['client'] as $class => $config) {
            if (class_exists($class)) {
                return new $class();
            }
        }

        return null;
    }

    public function findRequestFactory(): ?RequestFactoryInterface
    {
        foreach ($this->defaultImplementations['factory'] as $class => $config) {
            if (class_exists($class)) {
                return new $class();
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
}
