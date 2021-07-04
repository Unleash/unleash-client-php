<?php

namespace Rikudou\Unleash\DTO;

use JetBrains\PhpStorm\ExpectedValues;
use JsonSerializable;
use LogicException;
use Rikudou\Unleash\Enum\VariantPayloadType;

interface VariantPayload extends JsonSerializable
{
    #[ExpectedValues(valuesFromClass: VariantPayloadType::class)]
    public function getType(): string;

    /**
     * @return array<mixed>|string
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function getValue(): array|string;

    /**
     * @throws LogicException
     *
     * @return array<mixed>
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function fromJson(): array;
}
