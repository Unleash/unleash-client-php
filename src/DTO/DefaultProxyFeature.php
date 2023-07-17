<?php

namespace Unleash\Client\DTO;

final class DefaultProxyFeature implements ProxyFeature
{
    public string $name;

    public bool $enabled;

    public ProxyVariant $variant;

    public bool $impression_data;

    /**
     * @param array{
     *     name: string,
     *     enabled: bool,
     *     variant: array{
     *         name: string,
     *         enabled: bool,
     *         payload?: array{
     *             type: string,
     *             value: string
     *         }
     *     },
     *     impression_data: bool
     * } $response
     */
    public function __construct(array $response)
    {
        if (!isset($response['name'], $response['enabled'], $response['variant'], $response['impression_data'])) {
            throw new \InvalidArgumentException('Invalid response structure');
        }

        $this->name = $response['name'];
        $this->enabled = $response['enabled'];
        $this->impression_data = $response['impression_data'];

        $payload = null;

        if (isset($response['variant']['payload']['type'], $response['variant']['payload']['value'])) {
            $payload = new DefaultVariantPayload($response['variant']['payload']['type'], $response['variant']['payload']['value']);
        }

        $this->variant = new DefaultProxyVariant($response['variant']['name'], $response['variant']['enabled'], $payload);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getVariant(): ProxyVariant
    {
        return $this->variant;
    }

    public function hasImpressionData(): bool
    {
        return $this->impression_data;
    }
}