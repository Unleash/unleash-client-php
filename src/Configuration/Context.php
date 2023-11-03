<?php

namespace Unleash\Client\Configuration;

use DateTimeInterface;

interface Context
{
    public function getCurrentUserId(): ?string;

    public function getIpAddress(): ?string;

    public function getSessionId(): ?string;

    public function getCustomProperty(string $name): string;

    /**
     * @todo make $value nullable
     */
    public function setCustomProperty(string $name, string $value): self;

    public function hasCustomProperty(string $name): bool;

    public function removeCustomProperty(string $name, bool $silent = true): self;

    public function setCurrentUserId(?string $currentUserId): self;

    public function setIpAddress(?string $ipAddress): self;

    public function setSessionId(?string $sessionId): self;

    /**
     * @param array<string> $values
     */
    public function hasMatchingFieldValue(string $fieldName, array $values): bool;

    public function findContextValue(string $fieldName): ?string;

    public function getHostname(): ?string;

    public function setHostname(?string $hostname): self;

    public function getEnvironment(): ?string;

    public function setEnvironment(?string $environment): self;

    public function getCurrentTime(): DateTimeInterface;

    public function setCurrentTime(?DateTimeInterface $time): self;

    /**
     * @return array<string, string>
     */
    public function getCustomProperties(): array;
}
