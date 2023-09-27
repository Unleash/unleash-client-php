<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ArrayShape;

final class DefaultDepencency implements Dependency
{
    public function __construct(
        private readonly string $feature,
        private readonly ?bool $enabled,
        private readonly ?array $variants = null,
    ) {
    }

    #[ArrayShape(['name' => 'string', 'enabled' => 'bool', 'variants' => 'array'])]
    public function jsonSerialize(): array
    {
        $result = [
            'name' => $this->feature,
        ];
        if ($this->enabled !== null) {
            $result['enabled'] = $this->enabled;
        }
        if ($this->variants !== null) {
            $result['variants'] = $this->variants;
        }

        return $result;
    }

    public function getFeature(): string
    {
        return $this->feature;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function getVariants(): ?array
    {
        return $this->variants;
    }
}
