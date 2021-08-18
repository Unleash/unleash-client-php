<?php

namespace Unleash\Client\Configuration;

interface Context
{
    public function getCurrentUserId(): ?string;

    public function getIpAddress(): ?string;

    public function getSessionId(): ?string;

    public function getCustomProperty(string $name): string;

    /**
     * @return $this
     */
    public function setCustomProperty(string $name, string $value);

    public function hasCustomProperty(string $name): bool;

    /**
     * @return $this
     */
    public function removeCustomProperty(string $name, bool $silent = true);

    /**
     * @return $this
     */
    public function setCurrentUserId(?string $currentUserId);

    /**
     * @return $this
     */
    public function setIpAddress(?string $ipAddress);

    /**
     * @return $this
     */
    public function setSessionId(?string $sessionId);

    /**
     * @param array<string> $values
     */
    public function hasMatchingFieldValue(string $fieldName, array $values): bool;

    public function findContextValue(string $fieldName): ?string;
}
