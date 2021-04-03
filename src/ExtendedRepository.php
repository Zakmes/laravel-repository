<?php

declare(strict_types=1);

namespace Czim\Repository;

use Czim\Repository\Contracts\ExtendedRepositoryInterface;
use Czim\Repository\Criteria\Common\Scopes;
use Czim\Repository\Criteria\Common\UseCache;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Czim\Repository\Enums\CriteriaKey;

/**
 * Extends BaseRepository with extra functionality:
 *
 *      - setting default criteria to apply
 *      - active record filtering
 *      - caching (requires Rememberable or custom caching Criteria)
 *      - scopes
 */
abstract class ExtendedRepository extends BaseRepository implements ExtendedRepositoryInterface
{
    /**
     * Override if model has a basic 'active' field
     */
    protected bool $hasActive = false;

    /**
     * The column to check for if hasActive is true
     */
    protected string $activeColumn = 'active';

    /**
     * Setting: enables (remember) cache
     */
    protected bool $enableCache = false;

    /**
     * Setting: disables the active=1 check (if hasActive is true for repo)
     */
    protected bool $includeInactive = false;

    /**
     * Scopes to apply to queries
     * Must be supported by model used!
     */
    protected array $scopes = [];

    /**
     * Parameters for a given scope.
     * Note that you can only use each scope once, since parameters will be set by scope name as key.
     */
    protected array $scopeParameters = [];

    public function __construct(Container $app, Collection $collection)
    {
        parent::__construct($app, $collection);

        $this->refreshSettingDependentCriteria();
    }


    // -------------------------------------------------------------------------
    //      Criteria
    // -------------------------------------------------------------------------

    /**
     * Builds the default criteria and replaces the criteria stack to apply with
     * the default collection.
     *
     * Override to also refresh the default criteria for extended functionality.
     */
    public function restoreDefaultCriteria(): self
    {
        parent::restoreDefaultCriteria();

        $this->refreshSettingDependentCriteria();

        return $this;
    }

    /**
     * Refreshes named criteria, so that they reflect the current repository settings
     * (for instance for updating the Active check, when includeActive has changed)
     * This also makes sure the named criteria exist at all, if they are required and were never added.
     */
    public function refreshSettingDependentCriteria(): self
    {
        if ($this->hasActive) {
            if (! $this->includeInactive) {
                $this->criteria->put(CriteriaKey::ACTIVE, new Criteria\Common\IsActive( $this->activeColumn ));
            } else {
                $this->criteria->forget(CriteriaKey::ACTIVE);
            }
        }

        if ($this->enableCache) {
            $this->criteria->put(CriteriaKey::CACHE, $this->getCacheCriteriaInstance());
        } else {
            $this->criteria->forget(CriteriaKey::CACHE);
        }

        if (! empty($this->scopes)) {
            $this->criteria->put(CriteriaKey::SCOPE, $this->getScopesCriteriaInstance());
        } else {
            $this->criteria->forget(CriteriaKey::SCOPE);
        }

        return $this;
    }

    /**
     * Returns Criteria to use for caching. Override to replace with something other
     * than Rememberable (which is used by the default Common\UseCache Criteria);
     */
    protected function getCacheCriteriaInstance(): UseCache
    {
        return new Criteria\Common\UseCache();
    }


    /**
     * Returns Criteria to use for applying scopes. Override to replace with something
     * other the default Common\Scopes Criteria.
     *
     * @throws \Exception
     */
    protected function getScopesCriteriaInstance(): Scopes
    {
        return new Criteria\Common\Scopes( $this->convertScopesToCriteriaArray() );
    }


    // -------------------------------------------------------------------------
    //      Scopes
    // -------------------------------------------------------------------------

    /**
     * Adds a scope to enforce, overwrites with new parameters if it already exists
     */
    public function addScope(string $scope, array $parameters = []): self
    {
        if (! in_array($scope, $this->scopes)) {

            $this->scopes[] = $scope;
        }

        $this->scopeParameters[ $scope ] = $parameters;

        $this->refreshSettingDependentCriteria();
        return $this;
    }

    /**
     * Adds a scope to enforce
     */
    public function removeScope(string $scope): self
    {
        $this->scopes = array_diff($this->scopes, [ $scope ]);

        unset($this->scopeParameters[ $scope ]);

        $this->refreshSettingDependentCriteria();
        return $this;
    }

    /**
     * Clears any currently set scopes
     */
    public function clearScopes(): self
    {
        $this->scopes          = [];
        $this->scopeParameters = [];

        $this->refreshSettingDependentCriteria();
        return $this;
    }

    /**
     * Converts the tracked scopes to an array that the Scopes Common Criteria will eat.
     */
    protected function convertScopesToCriteriaArray(): array
    {
        $scopes = [];

        foreach ($this->scopes as $scope) {

            if (array_key_exists($scope, $this->scopeParameters) && ! empty($this->scopeParameters[ $scope ])) {
                $scopes[] = [$scope, $this->scopeParameters[$scope]];
                continue;
            }

            $scopes[] = [$scope, []];
        }

        return $scopes;
    }


    // -------------------------------------------------------------------------
    //      Maintenance mode / settings
    // -------------------------------------------------------------------------

    /**
     * Enables maintenance mode, ignoring standard limitations on model availability
     * and disables caching (if it was enabled).
     */
    public function maintenance(bool $enable = true): self
    {
        return $this->includeInactive($enable)->enableCache(! $enable);
    }

    /**
     * Prepares repository to include inactive entries
     * (entries with the $this->activeColumn set to false)
     */
    public function includeInactive(bool $enable = true): self
    {
        $this->includeInactive = (bool) $enable;
        $this->refreshSettingDependentCriteria();

        return $this;
    }

    /**
     * Prepares repository to exclude inactive entries
     */
    public function excludeInactive(): self
    {
        return $this->includeInactive(false);
    }

    /**
     * Returns whether inactive records are included
     */
    public function isInactiveIncluded(): bool
    {
        return $this->includeInactive;
    }

    /**
     * Enables using the cache for retrieval
     */
    public function enableCache(bool $enable = true): self
    {
        $this->enableCache = (bool) $enable;
        $this->refreshSettingDependentCriteria();

        return $this;
    }

    /**
     * Disables using the cache for retrieval
     */
    public function disableCache(): self
    {
        return $this->enableCache(false);
    }

    /**
     * Returns whether cache is currently active
     */
    public function isCacheEnabled(): bool
    {
        return $this->enableCache;
    }


    // -------------------------------------------------------------------------
    //      Manipulation
    // -------------------------------------------------------------------------

    /**
     * Update the active flag for a record
     */
    public function activateRecord(int $id, bool $active = true): bool
    {
        if (! $this->hasActive) {
            return false;
        }

        $model = $this->makeModel(false);

        if (! ($model = $model->find($id))) {
            return false;
        }

        $model->{$this->activeColumn} = (bool) $active;

        return $model->save();
    }
}
