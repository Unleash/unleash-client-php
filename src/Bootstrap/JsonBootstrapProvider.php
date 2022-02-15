<?php

namespace Unleash\Client\Bootstrap;

use JsonException;
use Unleash\Client\Exception\InvalidValueException;

final class JsonBootstrapProvider implements BootstrapProvider
{
    public function __construct(
        private readonly string $json,
    ) {
    }

    /**
     * @throws JsonException
     *
     * @return array<mixed>
     */
    public function getBootstrap(): array
    {
        $result = json_decode($this->json, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($result)) {
            throw new InvalidValueException(sprintf(
                'The provided json string must be a valid json object, %s given.',
                gettype($result),
            ));
        }

        return $result;
    }
}
