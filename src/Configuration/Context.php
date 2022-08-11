<?php

namespace Unleash\Client\Configuration;

use DateTimeInterface;

/**
 * @todo move to required methods in next major
 *
 * @method string|null       getHostname()
 * @method string|null       getEnvironment()
 * @method DateTimeInterface getCurrentTime()
 * @method Context           setHostname(string|null $hostname)
 * @method Context           setEnvironment(string|null $environment)
 * @method Context           setCurrentTime(DateTimeInterface|null $time)
 * @method array<string,     string> getCustomProperties()
 */
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
}
