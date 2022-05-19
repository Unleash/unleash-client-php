<?php

namespace Unleash\Client\Bootstrap;

use JsonSerializable;
use Traversable;

final class JsonSerializableBootstrapProvider implements BootstrapProvider
{
    /**
     * @var JsonSerializable|array<mixed>|Traversable<mixed>
     * @readonly
     */
    private $data;
    /**
     * @param JsonSerializable|array<mixed>|Traversable<mixed> $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }
    /**
     * @return mixed[]|\JsonSerializable|\Traversable
     */
    public function getBootstrap()
    {
        return $this->data;
    }
}
