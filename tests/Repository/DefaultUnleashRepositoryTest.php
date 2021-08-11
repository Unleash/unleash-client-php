<?php

namespace Rikudou\Tests\Unleash\Repository;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use Rikudou\Tests\Unleash\AbstractHttpClientTest;
use Rikudou\Tests\Unleash\Traits\FakeCacheImplementationTrait;
use Rikudou\Tests\Unleash\Traits\RealCacheImplementationTrait;
use Rikudou\Unleash\Configuration\UnleashConfiguration;
use Rikudou\Unleash\DTO\Feature;
use Rikudou\Unleash\Exception\HttpResponseException;
use Rikudou\Unleash\Repository\DefaultUnleashRepository;

final class DefaultUnleashRepositoryTest extends AbstractHttpClientTest
{
    use FakeCacheImplementationTrait, RealCacheImplementationTrait {
        FakeCacheImplementationTrait::getCache insteadof RealCacheImplementationTrait;

        RealCacheImplementationTrait::getCache as getRealCache;
    }

    private $response = [
        'version' => 1,
        'features' => [
            [
                'name' => 'test',
                'description' => '',
                'enabled' => true,
                'strategies' => [
                    [
                        'name' => 'flexibleRollout',
                        'parameters' => [
                            'groupId' => 'default',
                            'rollout' => '99',
                            'stickiness' => 'DEFAULT',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'test2',
                'description' => '',
                'enabled' => true,
                'strategies' => [
                    [
                        'name' => 'userWithId',
                        'parameters' => [
                            'userIds' => 'test,test2',
                        ],
                    ],
                ],
            ],
        ],
    ];

    public function testFindFeature()
    {
        $this->pushResponse($this->response, 3);
        self::assertInstanceOf(Feature::class, $this->repository->findFeature('test'));
        self::assertInstanceOf(Feature::class, $this->repository->findFeature('test2'));
        self::assertNull($this->repository->findFeature('test3'));
    }

    public function testGetFeatures()
    {
        $this->pushResponse($this->response, 2);
        self::assertCount(2, $this->repository->getFeatures());

        $features = $this->repository->getFeatures();
        self::assertEquals('test', $features[array_key_first($features)]->getName());
        self::assertEquals('flexibleRollout', $features[array_key_first($features)]->getStrategies()[0]->getName());
        self::assertEquals('test2', $features[array_key_last($features)]->getName());
        self::assertEquals('userWithId', $features[array_key_last($features)]->getStrategies()[0]->getName());

        $this->pushResponse([], 1, 401);
        $this->expectException(HttpResponseException::class);
        $this->repository->getFeatures();
    }

    public function testCache()
    {
        $cache = $this->getRealCache();
        $repository = new DefaultUnleashRepository(
            new Client([
                'handler' => HandlerStack::create($this->mockHandler),
            ]),
            new HttpFactory(),
            (new UnleashConfiguration('', '', ''))
                ->setCache($cache)
                ->setTtl(5)
        );

        $this->pushResponse($this->response, 2);
        $repository->getFeatures();
        $repository->getFeatures();
        self::assertEquals(1, $this->mockHandler->count());
        $cache->clear();

        $this->pushResponse($this->response);
        $feature = $repository->findFeature('test');
        sleep(6);
        self::assertEquals($feature->getName(), $repository->findFeature('test')->getName());
    }

    public function testCustomHeaders()
    {
        $this->pushResponse($this->response);
        $repository = new DefaultUnleashRepository(
            new Client([
                'handler' => $this->handlerStack,
            ]),
            new HttpFactory(),
            (new UnleashConfiguration('', '', ''))
                ->setHeaders([
                    'Custom-Header-1' => 'some value',
                    'Custom-Header-2' => 'some other value',
                    'Authorization' => 'Some API key',
                ])
            ->setCache($this->getCache())
        );

        $repository->getFeatures();

        self::assertCount(1, $this->requestHistory);
        $request = $this->requestHistory[0]['request'];
        assert($request instanceof Request);
        $headers = $request->getHeaders();
        self::assertArrayHasKey('Custom-Header-1', $headers);
        self::assertArrayHasKey('Custom-Header-2', $headers);
        self::assertArrayHasKey('Authorization', $headers);
        self::assertEquals('some value', $headers['Custom-Header-1'][0]);
        self::assertEquals('some other value', $headers['Custom-Header-2'][0]);
        self::assertEquals('Some API key', $headers['Authorization'][0]);
    }
}
