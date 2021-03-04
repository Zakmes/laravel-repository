<?php
namespace Czim\Repository\Contracts;

interface ExtendedRepositoryInterface
{
    /**
     * Refreshes named criteria, so that they reflect the current repository settings
     * (for instance for updating the Active check, when includeActive has changed)
     * This also makes sure the named criteria exist at all, if they are required and were never added.
     */
    public function refreshSettingDependentCriteria(): self;

    /**
     * Adds a scope to enforce, overwrites with new parameters if it already exists
     */
    public function addScope(string $scope, array $parameters = []): self;

    /**
     * Adds a scope to enforce
     */
    public function removeScope(string $scope): self;

    /**
     * Clears any currently set scopes
     */
    public function clearScopes(): self;

    /**
     * Enables maintenance mode, ignoring standard limitations on model availability
     */
    public function maintenance(bool $enable = true): self;

    /**
     * Prepares repository to include inactive entries
     * (entries with the $this->activeColumn set to false)
     */
    public function includeInactive(bool $enable = true): self;

    /**
     * Prepares repository to exclude inactive entries
     */
    public function excludeInactive(): self;

    /**
     * Enables using the cache for retrieval
     */
    public function enableCache(bool $enable = true): self;

    /**
     * Disables using the cache for retrieval
     */
    public function disableCache(): self;

    /**
     * Returns whether inactive records are included
     */
    public function isInactiveIncluded(): bool;

    /**
     * Returns whether cache is currently active
     */
    public function isCacheEnabled(): bool;
}
