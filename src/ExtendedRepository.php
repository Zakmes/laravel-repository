<?php

declare(strict_types=1);

namespace Czim\Repository;

use Czim\Repository\Contracts\ExtendedRepositoryInterface;
use Czim\Repository\Criteria\Common\Scopes;
use Czim\Repository\Criteria\Common\UseCache;
use Czim\Repository\Exceptions\RepositoryException;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Czim\Repository\Enums\CriteriaKey;

/**
 * Class ExtendedRepository
 * ----
 * Extends BaseRepository with extra functionality:
 *
 * - setting default criteria to apply
 * - active record filtering
 * - caching (requires Rememberable or custom caching Criteria)
 * - scopes
 *
 * @package Czim|Repository
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
     * Scopes to apply to queries. Must be supported by model used!
     */
    protected array $scopes = [];

    /**
     * Parameters for a given scope.
     * Note that you can only use each scope once, since parameters will be set by scope name as key.
     */
    protected array $scopeParameters = [];

    /**
     * ExtendedRepository constructor.
     *
     * @param  Container  $app
     * @param  Collection $collection
     * @return void
     *
     * @throws BindingResolutionException
     * @throws RepositoryException
     */
    public function __construct(Container $app, Collection $collection)
    {
        parent::__construct($app, $collection);

        $this->refreshSettingDependentCriteria();
    }

    /**
     * {@inheritdoc}
     */
    public function restoreDefaultCriteria(): self
    {
        parent::restoreDefaultCriteria();

        $this->refreshSettingDependentCriteria();

        return $this;
    }

    /**
     * {@inheritdoc}
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
     *
     * @return UseCache
     */
    protected function getCacheCriteriaInstance(): UseCache
    {
        return new UseCache();
    }

    /**
     * Returns Criteria to use for applying scopes. Override to replace with something
     * other the default Common\Scopes Criteria.
     *
     * @return Scopes
     *
     * @throws Exception
     */
    protected function getScopesCriteriaInstance(): Scopes
    {
        return new Criteria\Common\Scopes( $this->convertScopesToCriteriaArray() );
    }

    /**
     * {@inheritdoc}
     */
    public function addScope(string $scope, array $parameters = []): self
    {
        if (! in_array($scope, $this->scopes, true)) {
            $this->scopes[] = $scope;
        }

        $this->scopeParameters[ $scope ] = $parameters;
        $this->refreshSettingDependentCriteria();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeScope(string $scope): self
    {
        $this->scopes = array_diff($this->scopes, [$scope]);

        unset($this->scopeParameters[$scope]);

        $this->refreshSettingDependentCriteria();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clearScopes(): self
    {
        $this->scopes = [];
        $this->scopeParameters = [];
        $this->refreshSettingDependentCriteria();

        return $this;
    }

    /**
     * Converts the tracked scopes to an array that the Scopes Common Criteria will eat.
     *
     * @return array
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

    /**
     * {@inheritdoc}
     */
    public function maintenance(bool $enable = true): self
    {
        return $this->includeInactive($enable)->enableCache(! $enable);
    }

    /**
     * {@inheritdoc}
     */
    public function includeInactive(bool $enable = true): self
    {
        $this->includeInactive = $enable;
        $this->refreshSettingDependentCriteria();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function excludeInactive(): self
    {
        return $this->includeInactive(false);
    }

    /**
     * {@inheritdoc}
     */
    public function isInactiveIncluded(): bool
    {
        return $this->includeInactive;
    }

    /**
     * {@inheritdoc}
     */
    public function enableCache(bool $enable = true): self
    {
        $this->enableCache = $enable;
        $this->refreshSettingDependentCriteria();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function disableCache(): self
    {
        return $this->enableCache(false);
    }

    /**
     * {@inheritdoc}
     */
    public function isCacheEnabled(): bool
    {
        return $this->enableCache;
    }

    /**
     * Update the active flag for a record
     *
     * @param  int|string   $identifier The unique identifier from the database record.
     * @param  bool         $active     Parameter for setting the record as active or inactive
     * @return bool
     *
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function activateRecord(int|string $identifier, bool $active = true): bool
    {
        if (! $this->hasActive) {
            return false;
        }

        $model = $this->makeModel(false);

        if (! ($model = $model->find($identifier))) {
            return false;
        }

        $model->{$this->activeColumn} = $active;

        return $model->save();
    }
}
