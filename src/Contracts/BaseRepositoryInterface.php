<?php

declare(strict_types=1);

namespace Czim\Repository\Contracts;

use Closure;
use Czim\Repository\Exceptions\RepositoryException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

interface BaseRepositoryInterface
{
    /**
     * Returns specified model class name.
     *
     * Note that this is the only abstract method.
     */
    public function model(): string;

    /**
     * Creates instance of model to start building query for
     *
     * @param  bool $storeModel if true, this becomes a fresh $this->model property
     * @return EloquentBuilder
     *
     * @throws RepositoryException
     */
    public function makeModel(bool $storeModel = true): EloquentBuilder|Model;

    /**
     * Give un executed query for current criteria
     */
    public function query(): EloquentBuilder;

    /**
     * Does a simple count(*) for the model / scope
     */
    public function count(): int;

    /**
     * Returns first match
     */
    public function first(?array $columns = ['*']): ?Model;

    /**
     * Returns first match or throws exception if not found
     *
     * @throws ModelNotFoundException
     */
    public function firstOrFail(array $columns = ['*']): Model;

    /**
     * Method for getting all the results in the database table.
     */
    public function all(array $columns = ['*']): EloquentCollection;

    /**
     * Get an array with the values of a given key.
     */
    public function pluck(string $value, ?string $key = null): array;

    /**
     * Get an array with the values of a given key.
     *
     * @deprecated
     */
    public function lists(string $value, ?string $key = null): array;

    /**
     * Paginate the given query.
     */
    public function paginate(int $perPage, array $columns = ['*'], string $pageName = 'page', int|null $page = null): LengthAwarePaginator;

    /**
     * Find a model in the collection by key.
     */
    public function find(int|string $id, array $columns = ['*'], ?string $attribute = null): ?Model;

    /**
     * Returns first match or throws exception if not found
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(int|string $id, array $columns = ['*']): Model;

    /**
     * Find record by the attribute and value combination.
     */
    public function findBy(string $attribute, mixed $value, array $columns = ['*']): mixed;

    /**
     * Find all collection items matched by the attribute and value pair.
     */
    public function findAllBy(string $attribute, mixed $value, array $columns = ['*']): mixed;

    /**
     * Find a collection of models by the given query conditions.
     */
    public function findWhere(array $where, array $columns = ['*'], bool $or = false): ?Collection;

    /**
     * Makes a new model without persisting it
     */
    public function make(array $data): Model;

    /**
     * Creates a model and returns it
     */
    public function create(array $data): ?Model;

    /**
     * Updates a model by $id
     *
     * @param  array       $data
     * @param  mixed       $id
     * @param  string|null $attribute
     * @return bool  false if could not find model or not succesful in updating
     */
    public function update(array $data, mixed $id, ?string $attribute = null);

    /**
     * Finds and fills a model by id, without persisting changes
     */
    public function fill(array $data, mixed $id, ?string $attribute = null): null|Model|bool;

    /**
     * Deletes a model by $id
     */
    public function delete(int|string $id): bool;

    /**
     * Applies callback to query for easier elaborate custom queries
     * on all() calls.
     *
     * The callback must be query/builder compatible.
     *
     * @throws \Exception
     */
    public function allCallback(Closure $callback, array $columns = ['*']): Collection;

    /**
     * Applies callback to query for easier elaborate custom queries
     * on find (actually: ->first()) calls.
     *
     * The callback must be query/builder compatible.
     *
     * @throws \Exception
     */
    public function findCallback(Closure $callback, array $columns = ['*']): Collection|Model;


    /**
     * Returns a collection with the default criteria for the repository.
     * These should be the criteria that apply for (almost) all calls
     *
     * Default set of criteria to apply to this repository
     * Note that this also needs all the parameters to send to the constructor
     * of each (and this CANNOT be solved by using the classname of as key,
     * since the same Criteria may be applied more than once).
     */
    public function defaultCriteria(): Collection;

    /**
     * Builds the default criteria and replaces the criteria stack to apply with
     * the default collection.
     */
    public function restoreDefaultCriteria(): self;

    /**
     * Sets criteria to empty collection
     */
    public function clearCriteria(): self;

    /**
     * Sets or unsets ignoreCriteria flag. If it is set, all criteria (even
     * those set to apply once!) will be ignored.
     */
    public function ignoreCriteria(bool $ignore = true): self;

    /**
     * Returns a cloned set of all currently set criteria (not including
     * those to be applied once).
     */
    public function getCriteria(): Collection;

    /**
     * Applies Criteria to the model for the upcoming query
     *
     * This takes the default/standard Criteria, then overrides
     * them with whatever is found in the onceCriteria list
     */
    public function applyCriteria(): self;

    /**
     * Pushes Criteria, optionally by identifying key
     * If a criteria already exists for the key, it is overridden
     *
     * Note that this does NOT overrule any onceCriteria, even if set by key!
     *
     * @param CriteriaInterface $criteria
     * @param string|null       $key          unique identifier to store criteria as
     *                                        this may be used to remove and overwrite criteria
     *                                        empty for normal automatic numeric key
     * @return $this
     */
    public function pushCriteria(CriteriaInterface $criteria, ?string $key = null): self;

    /**
     * Removes criteria by key, if it exists
     */
    public function removeCriteria(string $key): self;

    /**
     * Pushes Criteria, but only for the next call, resets to default afterwards
     * Note that this does NOT work for specific criteria exclusively, it resets
     * to default for ALL Criteria.
     */
    public function pushCriteriaOnce(CriteriaInterface $criteria, ?string $key = null): self;

    /**
     * Removes Criteria, but only for the next call, resets to default afterwards
     * Note that this does NOT work for specific criteria exclusively, it resets
     * to default for ALL Criteria.
     *
     * In effect, this adds a NullCriteria to onceCriteria by key, disabling any criteria
     * by that key in the normal criteria list.
     */
    public function removeCriteriaOnce(string $key): self;
}
