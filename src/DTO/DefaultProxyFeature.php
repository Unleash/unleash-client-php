<?php

namespace Unleash\Client\DTO;

final class DefaultProxyFeature implements ProxyFeature
{
    public string $name;

    public bool $enabled;

    public Variant $variant;

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
        // This is validated elsewhere, this should only happen if a consumer
        // tries to new this up manually and isn't interesting to tests

        // @codeCoverageIgnoreStart
        if (!isset($response['name'], $response['enabled'], $response['variant'], $response['impression_data'])) {
            throw new \InvalidArgumentException('Invalid response structure');
        }
        // @codeCoverageIgnoreEnd

        $this->name = $response['name'];
        $this->enabled = $response['enabled'];
        $this->impression_data = $response['impression_data'];

        $payload = null;

        if (isset($response['variant']['payload']['type'], $response['variant']['payload']['value'])) {
            $payload = new DefaultVariantPayload($response['variant']['payload']['type'], $response['variant']['payload']['value']);
        }

        $this->variant = new DefaultVariant($response['variant']['name'], $response['variant']['enabled'], $payload);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getVariant(): Variant
    {
        return $this->variant;
    }

    public function hasImpressionData(): bool
    {
        return $this->impression_data;
    }
}
