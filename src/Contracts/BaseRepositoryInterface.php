<?php

declare(strict_types=1);

namespace Czim\Repository\Contracts;

use Closure;
use Czim\Repository\Exceptions\RepositoryException;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

interface BaseRepositoryInterface
{
    /**
     * Returns specified model class name.
     *
     * Note that this is the only abstract method.
     *
     * @return string
     */
    public function model(): string;

    /**
     * Creates instance of model to start building query for
     *
     * @param  bool $storeModel if true, this becomes a fresh $this->model property
     * @return EloquentBuilder|Model
     *
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function makeModel(bool $storeModel = true): EloquentBuilder | Model;

    /**
     * Give un executed query for current criteria
     *
     * @return EloquentBuilder
     *
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function query(): EloquentBuilder;

    /**
     * Does a simple count(*) for the model/scope
     *
     * @return int
     *
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function count(): int;

    /**
     * Returns first match
     *
     * @param  array $columns The colums u want to select from your query output
     * @return Model|null
     *
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function first(array $columns = ['*']): ?Model;

    /**
     * Returns first match or throws exception if not found
     *
     * @param  array $columns   The columns that u wish to use from the collection.
     * @return Model
     *
     * @throws ModelNotFoundException
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function firstOrFail(array $columns = ['*']): Model;

    /**
     * Method for getting all the results in the database table.
     *
     * @param  array $columns   The columns that u wish to use from the collection.
     * @return EloquentCollection
     *
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function all(array $columns = ['*']): EloquentCollection;

    /**
     * Get an array with the values of a given key.
     *
     * @param  string      $value   The value for the array
     * @param  string|null $key     The key for the array
     * @return array
     *
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function pluck(string $value, string|null $key = null): array;

    /**
     * Get an array with the values of a given key.
     *
     * @deprecated
     *
     * @param  string      $value   The value for the array
     * @param  string|null $key     The key for the array
     * @return array
     *
     * @throws BindingResolutionException
     * @throws RepositoryException
     */
    public function lists(string $value, ?string $key = null): array;

    /**
     * Paginate the given query.
     *
     * @param  int      $perPage    The amount of records per page.
     * @param  array    $columns    The columns u wish to use from the collection.
     * @param  string   $pageName   The name for the page parameter in the url
     * @param  int|null $page       The indicator for tha page where the user is at.
     * @return LengthAwarePaginator
     *
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function paginate(int $perPage, array $columns = ['*'], string $pageName = 'page', int|null $page = null): LengthAwarePaginator;

    /**
     * Find a model in the collection by key.
     *
     * @param  int|string  $id          The identifier for the database record. It will be used in an WHERE clause
     * @param  array       $columns     The columns that u wish to use from the collection.
     * @param  string|null $attribute   The database table column that will be used in the WHERE clause
     * @return Model|null
     *
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function find(int|string $id, array $columns = ['*'], ?string $attribute = null): ?Model;

    /**
     * Returns first match or throws exception if not found
     *
     * @param  int|string $id       The unique identifier from the database record.
     * @param  array      $columns  The database columns that u wish to use from the collection
     * @return Model
     *
     * @throws ModelNotFoundException
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function findOrFail(int|string $id, array $columns = ['*']): Model;

    /**
     * Find record by the attribute and value combination.
     *
     * @param  string $attribute    The database table his column name for the WHERE clause
     * @param  mixed  $value        The value for the where clause
     * @param  array  $columns      The database columns that u wish to use from the collection.
     * @return EloquentBuilder|Model|null
     *
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function findBy(string $attribute, mixed $value, array $columns = ['*']): EloquentBuilder|Model|null;

    /**
     * Find all collection items matched by the attribute and value pair.
     *
     * @param  string $attribute    The database table his column name for the WHERE clause
     * @param  mixed  $value        The value for the where clause
     * @param  array  $columns      The database columns that u wish to use from the collection.
     * @return mixed
     *
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function findAllBy(string $attribute, mixed $value, array $columns = ['*']): mixed;

    /**
     * Find a collection of models by the given query conditions.
     *
     * @param  array $where     The data array for the where clauses.
     * @param  array $columns   The database columns that u wish to use from the query result.
     * @param  bool  $orWhere   Configuration flag for determining is OR WHERE clauses will be used or not.
     * @return Collection|null
     *
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function findWhere(array $where, array $columns = ['*'], bool $orWhere = false): ?Collection;

    /**
     * Makes a new model without persisting it
     *
     * @param  array $data  The data for the database record that u want to create.
     * @return Model
     *
     * @throws MassAssignmentException
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function make(array $data): Model;

    /**
     * Creates a model and returns it
     *
     * @param  array $data  The data that u want to store in the database table.
     * @return Model|null
     *
     * @throws BindingResolutionException
     * @throws RepositoryException
     */
    public function create(array $data): ?Model;

    /**
     * Updates a model by $id
     *
     * Returns false when the model couldn't updated or is not found
     * in the database storage.
     *
     * @param  array       $data      The new data for the database record.
     * @param  mixed       $id        The identifier for the where clause
     * @param  string|null $attribute The database column name for the where clause.
     * @return bool
     *
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function update(array $data, mixed $id, ?string $attribute = null): bool;

    /**
     * Finds and fills a model by id, without persisting changes?
     *
     * @param  array       $data      The data which will be used to fill the database record.
     * @param  mixed       $id        The identifier for the where clause.
     * @param  string|null $attribute The database column name for the where cause
     * @return Model|bool|null
     *
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function fill(array $data, mixed $id, ?string $attribute = null): null|Model|bool;

    /**
     * Deletes a model by $id
     *
     * @param  string|int $id The unique identifier from the resource that u want to delete.
     * @return int
     *
     * @throws BindingResolutionException
     * @throws RepositoryException
     */
    public function delete(int|string $id): int;

    /**
     * Applies callback to query for easier elaborate custom queries
     * on all() calls.
     *
     * The callback must be query/builder compatible.
     *
     * @param  Closure $callback    The closure that acts as an callback in the repository.
     * @param  array   $columns     The columns u want to use from the database table.
     * @return Collection
     *
     * @throws Exception
     */
    public function allCallback(Closure $callback, array $columns = ['*']): Collection;

    /**
     * Applies callback to query for easier elaborate custom queries
     * on find (actually: ->first()) calls.
     *
     * The callback must be query/builder compatible.
     *
     * @param  Closure $callback    The closure that acts as an callback in the repository.
     * @param  array   $columns     The columns u want to use from the database table.
     * @return Collection|Model
     *
     * @throws Exception
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
     *
     * @return self
     */
    public function restoreDefaultCriteria(): self;

    /**
     * Sets criteria to empty collection
     *
     * @return self
     */
    public function clearCriteria(): self;

    /**
     * Sets or unsets ignoreCriteria flag. If it is set, all criteria (even
     * those set to apply once!) will be ignored.
     *
     * @param  bool $ignore The status flag for configuring if u want to ignore the criteria.
     * @return self
     */
    public function ignoreCriteria(bool $ignore = true): self;

    /**
     * Returns a cloned set of all currently set criteria (not including
     * those to be applied once).
     *
     * @return Collection
     */
    public function getCriteria(): Collection;

    /**
     * Applies Criteria to the model for the upcoming query
     *
     * This takes the default/standard Criteria, then overrides
     * them with whatever is found in the onceCriteria list
     *
     * @return self
     */
    public function applyCriteria(): self;

    /**
     * Pushes Criteria, optionally by identifying key
     * If a criteria already exists for the key, it is overridden
     *
     * Note that this does NOT overrule any onceCriteria, even if set by key!
     *
     * @param CriteriaInterface $criteria     The criteria interface that u want to push once.
     * @param string|null       $key          unique identifier to store criteria as
     *                                        this may be used to remove and overwrite criteria
     *                                        empty for normal automatic numeric key
     * @return $this
     */
    public function pushCriteria(CriteriaInterface $criteria, ?string $key = null): self;

    /**
     * Removes criteria by key, if it exists
     *
     * @param  string $key The unique key of the criteria that you want to remove.
     * @return self
     */
    public function removeCriteria(string $key): self;

    /**
     * Pushes Criteria, but only for the next call, resets to default afterwards
     * Note that this does NOT work for specific criteria exclusively, it resets
     * to default for ALL Criteria.
     *
     * @param  CriteriaInterface    $criteria The criteria interface that u want to push only once.
     * @param  string|null          $key      The unique key of the criteria.
     * @return self
     */
    public function pushCriteriaOnce(CriteriaInterface $criteria, ?string $key = null): self;

    /**
     * Removes Criteria, but only for the next call, resets to default afterwards
     * Note that this does NOT work for specific criteria exclusively, it resets
     * to default for ALL Criteria.
     *
     * In effect, this adds a NullCriteria to onceCriteria by key, disabling any criteria
     * by that key in the normal criteria list.
     *
     * @param  string $key The unique key of the criteria that u want to remove once.
     * @return self
     */
    public function removeCriteriaOnce(string $key): self;
}
