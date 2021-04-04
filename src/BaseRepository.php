<?php

namespace Czim\Repository;

use Czim\Repository\Contracts\BaseRepositoryInterface;
use Czim\Repository\Contracts\CriteriaInterface;
use Czim\Repository\Criteria\NullCriteria;
use Czim\Repository\Exceptions\RepositoryException;
use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Support\Collection;
use Illuminate\Container\Container as App;
use InvalidArgumentException;
use JetBrains\PhpStorm\Pure;

/**
 * Basic repository for retrieving and manipulating Eloquent models.
 *
 * One of the main differences with Bosnadev's repository is that With this,
 * criteria may be given a key identifier, by which they may later be removed
 * or overridden. This way you can, for instance, set a default criterion for
 * ordering by a certain column, but in other cases, without instantiating, order
 * by other columns, by marking the Criteria that does the ordering with key 'order'.
 *
 * implements Contracts\RepositoryInterface, Contracts\RepositoryCriteriaInterface
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model|EloquentBuilder $model;

    /**
     * Criteria to keep and use for all coming queries
     */
    protected Collection|CriteriaInterface $criteria;

    /**
     * The Criteria to only apply to the next query
     */
    protected Collection|CriteriaInterface|array $onceCriteria;

    /**
     * List of criteria that are currently active (updates when criteria are stripped)
     * So this is a dynamic list that can change during calls of various repository
     * methods that alter the active criteria.
     */
    protected array|Collection|CriteriaInterface|null $activeCriteria = null;

    /**
     * Whether to skip ALL criteria
     */
    protected bool $ignoreCriteria = false;

    /**
     * Default number of paginated items
     */
    protected int $perPage = 1;


    /**
     * BaseRepository Constructor
     *
     * @param  App        $app
     * @param  Collection $collection
     * @return void
     *
     * @throws BindingResolutionException
     * @throws RepositoryException
     */
    public function __construct(protected App $app, Collection $collection)
    {
        if ($collection->isEmpty()) {
            $collection = $this->defaultCriteria();
        }

        $this->criteria       = $collection;
        $this->onceCriteria   = new Collection();
        $this->activeCriteria = new Collection();

        $this->makeModel();
    }


    /** {@inheritdoc} */
    public abstract function model(): string;

    /** {@inheritdoc} */
    public function makeModel(bool $storeModel = true): EloquentBuilder|Model
    {
        $model = $this->app->make($this->model());

        if (! $model instanceof Model) {
            throw new RepositoryException("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        if ($storeModel) {
            $this->model = $model;
        }

        return $model;
    }


    // -------------------------------------------------------------------------
    //      Retrieval methods
    // -------------------------------------------------------------------------

    /** {@inheritdoc} */
    public function query(): EloquentBuilder
    {
        $this->applyCriteria();

        if ($this->model instanceof Model) {
            return $this->model->query();
        }

        return clone $this->model;
    }

    /** {@inheritdoc} */
    public function count(): int
    {
        return $this->query()->count();
    }

    /** {@inheritdoc} */
    public function first(array|null|string $columns = ['*']): ?Model
    {
        return $this->query()->first($columns);
    }

    /** {@inheritdoc} */
    public function firstOrFail(array $columns = ['*']): Model
    {
        $result = $this->query()->first($columns);

        if (! empty($result)) {
            return $result;
        }

        throw (new ModelNotFoundException())->setModel($this->model());
    }

    /** {@inheritdoc} */
    public function all(array|string $columns = ['*']): DatabaseCollection
    {
        return $this->query()->get($columns);
    }

    /** {@inheritdoc} */
    public function pluck(string $value, ?string $key = null): array
    {
        $this->applyCriteria();

        $lists = $this->model->pluck($value, $key);

        if (is_array($lists)) {
            return $lists;
        }

        return $lists->all();
    }

    /** {@inheritdoc} */
    public function lists(string $value, ?string $key = null): array
    {
        return $this->pluck($value, $key);
    }

    /** {@inheritdoc} */
    public function paginate(int $perPage = null, array $columns = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginator
    {
        $perPage = $perPage ?: $this->getDefaultPerPage();

        return $this->query()->paginate($perPage, $columns, $pageName, $page);
    }

    /** {@inheritdoc} */
    public function find(mixed $id, array $columns = ['*'], ?string $attribute = null): ?Model
    {
        $query = $this->query();

        if (null !== $attribute && $attribute !== $query->getModel()->getKeyName()) {
            return $query->where($attribute, $id)->first($columns);
        }

        return $query->find($id, $columns);
    }

    /** {@inheritdoc} */
    public function findOrFail(int|string $id, array $columns = ['*']): Model
    {
        $result = $this->query()->find($id, $columns);

        if (! empty($result)) {
            return $result;
        }

        throw (new ModelNotFoundException())->setModel($this->model(), $id);
    }

    /** {@inheritdoc} */
    public function findBy(string $attribute, mixed $value, array $columns = ['*']): EloquentBuilder|Model|null
    {
        return $this->query()->where($attribute, $value)->first($columns);
    }

    /** {@inheritdoc} */
    public function findAllBy(string $attribute, mixed $value, array $columns = ['*']): mixed
    {
        return $this->query()->where($attribute, $value)->get($columns);
    }

    /** {@inheritdoc} */
    public function findWhere(array|Arrayable $where, array $columns = ['*'], bool $or = false): ?Collection
    {
        $model = $this->query();

        foreach ($where as $field => $value) {

            if ($value instanceof Closure) {

                $model = ( ! $or)
                    ? $model->where($value)
                    : $model->orWhere($value);

            } elseif (is_array($value)) {

                if (count($value) === 3) {

                    list($field, $operator, $search) = $value;

                    $model = ( ! $or)
                        ? $model->where($field, $operator, $search)
                        : $model->orWhere($field, $operator, $search);

                } elseif (count($value) === 2) {

                    list($field, $search) = $value;

                    $model = ! $or
                        ? $model->where($field, $search)
                        : $model->orWhere($field, $search);
                }

            } else {
                $model = ! $or
                    ? $model->where($field, $value)
                    : $model->orWhere($field, $value);
            }
        }

        return $model->get($columns);
    }


    // -------------------------------------------------------------------------
    //      Manipulation methods
    // -------------------------------------------------------------------------

    /** {@inheritdoc} */
    public function make(array $data): Model
    {
        return $this->makeModel(false)->fill($data);
    }

    /** {@inheritdoc} */
    public function create(array $data): ?Model
    {
        return $this->makeModel(false)->create($data);
    }

    /** {@inheritdoc} */
    public function update(array $data, mixed $id, ?string $attribute = null): bool
    {
        $model = $this->find($id, ['*'], $attribute);

        if (empty($model)) {
            return false;
        }

        return $model->fill($data)->save();
    }

    /** {@inheritdoc} */
    public function fill(array $data, mixed $id, ?string $attribute = null): Model|bool
    {
        $model = $this->find($id, ['*'], $attribute);

        if (empty($model)) {
            throw (new ModelNotFoundException())->setModel($this->model());
        }

        return $model->fill($data);
    }

    /** {@inheritdoc} */
    public function delete(mixed $id): bool
    {
        return $this->makeModel(false)->destroy($id);
    }


    // -------------------------------------------------------------------------
    //      With custom callback
    // -------------------------------------------------------------------------

    /** {@inheritdoc} */
    public function allCallback(Closure $callback, array $columns = ['*']): Collection
    {
        /** @var EloquentBuilder $result */
        $result = $callback($this->query());

        $this->checkValidCustomCallback($result);

        return $result->get($columns);
    }

    /** {@inheritdoc} */
    public function findCallback(Closure $callback, array $columns = ['*']): Collection|Model
    {
        /** @var EloquentBuilder $result */
        $result = $callback( $this->query() );

        $this->checkValidCustomCallback($result);

        return $result->first($columns);
    }

    /**
     * Check if the returned custom callback is valid.
     *
     * @throws InvalidArgumentException
     */
    protected function checkValidCustomCallback(string|EloquentBuilder|DatabaseBuilder|Model $result): void
    {
        if (! is_a($result, Model::class) && ! is_a($result, EloquentBuilder::class) && ! is_a($result, DatabaseBuilder::class)) {
            throw new InvalidArgumentException('Incorrect allCustom call in repository. The callback must return a QueryBuilder/EloquentBuilder or Model object.');
        }
    }


    // -------------------------------------------------------------------------
    //      Criteria
    // -------------------------------------------------------------------------

    /**
     * Returns a collection with the default criteria for the repository.
     * These should be the criteria that apply for (almost) all calls
     *
     * Default set of criteria to apply to this repository
     * Note that this also needs all the parameters to send to the constructor
     * of each (and this CANNOT be solved by using the classname of as key,
     * since the same Criteria may be applied more than once).
     *
     * Override with your own defaults (check ExtendedRepository's refreshed,
     * named Criteria for examples).
     */
    public function defaultCriteria(): Collection
    {
        return new Collection();
    }

    /** {@inheritdoc} */
    public function restoreDefaultCriteria(): self
    {
        $this->criteria = $this->defaultCriteria();

        return $this;
    }

    /** {@inheritdoc} */
    public function clearCriteria(): self
    {
        $this->criteria = new Collection();

        return $this;
    }

    public function ignoreCriteria(bool $ignore = true): self
    {
        $this->ignoreCriteria = $ignore;

        return $this;
    }

    /**
     * Returns a cloned set of all currently set criteria (not including
     * those to be applied once).
     */
    public function getCriteria(): Collection
    {
        return clone $this->criteria;
    }

    /**
     * Returns a cloned set of all currently set once criteria.
     */
    public function getOnceCriteria(): Collection|CriteriaInterface
    {
        return clone $this->onceCriteria;
    }

    /**
     * Returns a cloned set of all currently set criteria (not including
     * those to be applied once).
     */
    public function getAllCriteria(): Collection|CriteriaInterface
    {
        return $this->getCriteria()->merge($this->getOnceCriteria());
    }

    /**
     * Returns the criteria that must be applied for the next query
     */
    protected function getCriteriaToApply(): Collection|CriteriaInterface
    {
        // get the standard criteria
        $criteriaToApply = $this->getCriteria();

        // overrule them with criteria to be applied once
        if ( ! $this->onceCriteria->isEmpty()) {

            foreach ($this->onceCriteria as $onceKey => $onceCriteria) {

                // if there is no key, we can only add the criteria
                if (is_numeric($onceKey)) {

                    $criteriaToApply->push($onceCriteria);
                    continue;
                }

                // if there is a key, override or remove
                // if Null, remove criteria
                if (empty($onceCriteria) || is_a($onceCriteria, NullCriteria::class)) {

                    $criteriaToApply->forget($onceKey);
                    continue;
                }

                // otherwise, overide the criteria
                $criteriaToApply->put($onceKey, $onceCriteria);
            }
        }

        return $criteriaToApply;
    }

    /**
     * Applies Criteria to the model for the upcoming query
     *
     * This takes the default/standard Criteria, then overrides
     * them with whatever is found in the onceCriteria list
     *
     * @throws RepositoryException
     * @throws BindingResolutionException
     */
    public function applyCriteria(): self
    {
        // if we're ignoring criteria, the model must be remade without criteria
        if ($this->ignoreCriteria === true) {

            // and make sure that they are re-applied when we stop ignoring
            if (! $this->activeCriteria->isEmpty()) {
                $this->makeModel();
                $this->activeCriteria = new Collection();
            }

            return $this;
        }

        if ($this->areActiveCriteriaUnchanged()) return $this;

        // if the new Criteria are different, clear the model and apply the new Criteria
        $this->makeModel();
        $this->markAppliedCriteriaAsActive();


        // apply the collected criteria to the query
        foreach ($this->getCriteriaToApply() as $criteria) {
            if ($criteria instanceof CriteriaInterface) {
                $this->model = $criteria->apply($this->model, $this);
            }
        }

        $this->clearOnceCriteria();

        return $this;
    }

    /**
     * Checks whether the criteria that are currently pushed
     * are the same as the ones that were previously applied
     *
     * @return bool
     */
    protected function areActiveCriteriaUnchanged(): bool
    {
        return $this->onceCriteria->isEmpty() &&  $this->criteria == $this->activeCriteria;
    }

    /**
     * Marks the active criteria so we can later check what
     * is currently active
     *
     * @return void
     */
    protected function markAppliedCriteriaAsActive(): void
    {
        $this->activeCriteria = $this->getCriteriaToApply();
    }

    /**
     * After applying, removes the criteria that should only have applied once
     */
    protected function clearOnceCriteria(): void
    {
        if (! $this->onceCriteria->isEmpty()) {
            $this->onceCriteria = new Collection();
        }
    }

    /**
     * Pushes Criteria, optionally by identifying key
     * If a criteria already exists for the key, it is overridden
     *
     * Note that this does NOT overrule any onceCriteria, even if set by key!
     *
     * @param  CriteriaInterface $criteria
     * @param  string|null       $key       unique identifier to store criteria as
     *                                      this may be used to remove and overwrite criteria
     *                                      empty for normal automatic numeric key
     * @return $this
     */
    public function pushCriteria(CriteriaInterface $criteria, ?string $key = null): self
    {
        // standard bosnadev behavior
        if (is_null($key)) {

            $this->criteria->push($criteria);
            return $this;
        }

        // set/override by key
        $this->criteria->put($key, $criteria);

        return $this;
    }

    /** {@inheritdoc} */
    public function removeCriteria(string $key): self
    {
        $this->criteria->forget($key);

        return $this;
    }

    /**
     * Pushes Criteria, but only for the next call, resets to default afterwards
     * Note that this does NOT work for specific criteria exclusively, it resets
     * to default for ALL Criteria.
     */
    public function pushCriteriaOnce(CriteriaInterface $criteria, ?string $key = null): self
    {
        if (is_null($key)) {

            $this->onceCriteria->push($criteria);
            return $this;
        }

        // set/override by key
        $this->onceCriteria->put($key, $criteria);

        return $this;
    }


    /** {@inheritdoc} */
    public function removeCriteriaOnce(string $key): self
    {
        // if not present in normal list, there is nothing to override
        if ( ! $this->criteria->has($key)) return $this;

        // override by key with Null-value
        $this->onceCriteria->put($key, new NullCriteria);

        return $this;
    }

    /**
     * (misc): Returns default per page count.
     *
     * @return int
     */
    protected function getDefaultPerPage(): int
    {
        $perPage = (is_numeric($this->perPage) && $this->perPage > 0)
            ? $this->perPage
            : $this->makeModel(false)->getPerPage();

        return config('repository.perPage', $perPage);
    }
}
