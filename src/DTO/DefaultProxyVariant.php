<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ArrayShape;

final class DefaultProxyVariant implements ProxyVariant
{
    public function __construct(
        private readonly string $name,
        private readonly bool $enabled,
        private readonly ?VariantPayload $payload = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPayload(): ?VariantPayload
    {
        return $this->payload;
    }

    /**
     * @phpstan-return array<string|bool|array<string>>
     */
    #[ArrayShape(['name' => 'string', 'enabled' => 'bool', 'payload' => 'mixed'])]
    public function jsonSerialize(): array
    {
        $result = [
            'name' => $this->name,
            'enabled' => $this->enabled,
        ];
        if ($this->payload !== null) {
            $result['payload'] = $this->payload->jsonSerialize();
            assert(is_array($result['payload']));
        }

        return $result;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
