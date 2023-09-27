<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ArrayShape;

final class DefaultVariant implements Dependency
{
    /**
     * @param array<VariantOverride> $overrides
     */
    public function __construct(
        private readonly string $name,
        private readonly ?bool $enabled,
        private readonly ?array $variants = null,
    ) {
    }

    #[ArrayShape(['name' => 'string', 'enabled' => 'bool', 'variants' => 'array'])]
    public function jsonSerialize(): array
    {
        $result = [
            'name' => $this->name,
        ];
        if ($this->enabled !== null) {
            $result['enabled'] = $this->enabled;
        }
        if ($this->variants !== null) {
            $result['variants'] = $this->variants;
        }

        return $result;
    }

    public function getName(): string
    {
        return $this->name;
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
