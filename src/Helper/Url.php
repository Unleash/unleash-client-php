<?php

namespace Unleash\Client\Helper;

final class Url
{
    /**
     * @param array<string>|null $tags
     */
    public function __construct(
        private string $url,
        private ?string $namePrefix = null,
        private ?array $tags = null,
    ) {
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

        if (str_ends_with($url, '&') || str_ends_with($url, '?')) {
            $url = substr($url, 0, -1);
        }

        return $url;
    }
}
