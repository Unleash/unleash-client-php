<?php

namespace Unleash\Client\Bootstrap;

use JsonSerializable;
use Override;
use Traversable;

final readonly class JsonSerializableBootstrapProvider implements BootstrapProvider
{
    /**
     * @param JsonSerializable|array<mixed>|Traversable<mixed> $data
     */
    public function __construct(
        private JsonSerializable|array|Traversable $data,
    ) {
    }

    /**
     * @return array<mixed>|JsonSerializable|Traversable<mixed>
     */
    #[Override]
    public function getBootstrap(): array|JsonSerializable|Traversable
    {
        return $this->data;
    }
}
