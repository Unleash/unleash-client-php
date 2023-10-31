<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ArrayShape;

final class DefaultDepencency implements Dependency
{
    /**
     * @param array<string> $variants
     */
    public function __construct(
        private readonly Feature|string $feature,
        private readonly ?bool $enabled,
        private readonly ?array $variants = null,
    ) {
    }

    /**
     * @phpstan-return array<string|bool|array<string>>
     */
    #[ArrayShape(['name' => 'string', 'enabled' => 'bool', 'variants' => 'array'])]
    public function jsonSerialize(): array
    {
        $result = [
            'name' => is_string($this->feature) ? $this->feature : $this->feature->getName(),
        ];
        if ($this->enabled !== null) {
            $result['enabled'] = $this->enabled;
        }
        if ($this->variants !== null) {
            $result['variants'] = $this->variants;
        }

        return $result;
    }

    public function getFeature(): Feature|string
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
