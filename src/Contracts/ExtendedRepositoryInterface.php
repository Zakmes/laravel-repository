<?php

declare(strict_types=1);

namespace Czim\Repository\Contracts;

/**
 * Interface ExtendedRepositoryInterface
 *
 * @package Czim\Repository\Contracts
 */
interface ExtendedRepositoryInterface
{
    /**
     * Refreshes named criteria, so that they reflect the current repository settings
     * (for instance for updating the Active check, when includeActive has changed)
     * This also makes sure the named criteria exist at all, if they are required and were never added.
     *
     * @return self
     */
    public function refreshSettingDependentCriteria(): self;

    /**
     * Adds a scope to enforce, overwrites with new parameters if it already exists
     *
     * @param  string $scope        The name from the scope u want to add;
     * @param  array  $parameters   The parameters for the scope.
     * @return self
     */
    public function addScope(string $scope, array $parameters = []): self;

    /**
     * Adds a scope to enforce
     *
     * @param  string $scope The name of the scope u want to remove
     * @return self
     */
    public function removeScope(string $scope): self;

    /**
     * Clears any currently set scopes
     *
     * @return self
     */
    public function clearScopes(): self;

    /**
     * Enables maintenance mode, ignoring standard limitations on model availability
     *
     * @param  bool $enable Configuration flag for enabling/disabling the maintenance mode.
     * @return self
     */
    public function maintenance(bool $enable = true): self;

    /**
     * Prepares repository to include inactive entries
     * (entries with the $this->activeColumn set to false)
     *
     * @param  bool $enable The configuration flag for include/exclude inactive entries
     * @return self
     */
    public function includeInactive(bool $enable = true): self;

    /**
     * Prepares repository to exclude inactive entries
     *
     * @return self
     */
    public function excludeInactive(): self;

    /**
     * Enables using the cache for retrieval
     *
     * @param  bool $enable The configuration boolean for enabling/disabling the cache usage.
     * @return self
     */
    public function enableCache(bool $enable = true): self;

    /**
     * Disables using the cache for retrieval
     *
     * @return self
     */
    public function disableCache(): self;

    /**
     * Returns whether inactive records are included
     *
     * @return bool
     */
    public function isInactiveIncluded(): bool;

    /**
     * Returns whether cache is currently active
     *
     * @return bool
     */
    public function isCacheEnabled(): bool;
}
