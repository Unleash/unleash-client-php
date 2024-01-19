<?php

namespace Unleash\Client\Helper;

final class Url
{
    /**
     * @readonly
     * @var string
     */
    private $url;
    /**
     * @readonly
     * @var string|null
     */
    private $namePrefix;
    /**
     * @var array<string>|null
     * @readonly
     */
    private $tags;
    /**
     * @param array<string>|null $tags
     */
    public function __construct(string $url, ?string $namePrefix = null, ?array $tags = null)
    {
        $this->url = $url;
        $this->namePrefix = $namePrefix;
        $this->tags = $tags;
    }
    public function __toString(): string
    {
        $url = $this->url;

        if ($this->namePrefix !== null || $this->tags !== null) {
            $url .= '?';
        }

        if ($this->namePrefix !== null && $this->namePrefix !== '') {
            $url .= sprintf('namePrefix=%s&', urlencode($this->namePrefix));
        }

        if ($this->tags !== null) {
            foreach ($this->tags as $name => $value) {
                $url .= sprintf('tag=%s&', urlencode("{$name}:{$value}"));
            }
        }

        if (substr_compare($url, '&', -strlen('&')) === 0 || substr_compare($url, '?', -strlen('?')) === 0) {
            $url = substr($url, 0, -1);
        }

        return $url;
    }
}
