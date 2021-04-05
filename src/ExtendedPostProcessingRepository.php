<?php

declare(strict_types=1);

namespace Czim\Repository;

use Czim\Repository\Contracts\CriteriaInterface;
use Czim\Repository\Contracts\PostProcessingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Czim\Repository\Contracts\PostProcessorInterface;
use Czim\Repository\PostProcessors\ApplyExtraHiddenAndVisibleAttributes;
use Closure;
use Illuminate\Container\Container as App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use ReflectionClass;

/**
 * Extends the ExtendedRepository with PostProcessing functionality,
 * including convenience methods for hiding/unhiding Model properties.
 */
abstract class ExtendedPostProcessingRepository extends ExtendedRepository implements PostProcessingRepositoryInterface
{
    protected string|array $extraHidden = [];
    protected string|array $extraUnhidden = [];

    /**
     * The postprocessors to apply to the returned results for the repository
     * (only all() and find(), and similar calls)
     *
     * @var Collection
     */
    protected $postProcessors;

    /**
     * @param App                            $app
     * @param Collection|CriteriaInterface[] $collection
     */
    public function __construct(App $app, Collection $collection)
    {
        parent::__construct($app, $collection);

        $this->restoreDefaultPostProcessors();
    }


    /**
     * Returns the default list of postprocessors to apply to models
     * before returning the result to anything using the repository.
     *
     * Each entry is combination of a key (the classname of the postprocessor)
     * and a value (set of parameters, or Closure that generates parameters).
     *
     * The idea is that on each call, the postprocessors are instantiated,
     * and the parameters (if any) set for them, so any updates on the
     * repository are reflected by the processors.
     */
    public function defaultPostProcessors(): Collection|PostProcessorInterface
    {
        return new Collection([
            ApplyExtraHiddenAndVisibleAttributes::class => fn() => [$this->extraHidden, $this->extraUnhidden],
        ]);
    }


    // -------------------------------------------------------------------------
    //      PostProcessors
    // -------------------------------------------------------------------------

    /**
     * Restores postprocessors to default collection
     */
    public function restoreDefaultPostProcessors(): self
    {
        $this->postProcessors = $this->defaultPostProcessors();

        return $this;
    }

    /**
     * Pushes a postProcessor to apply to all models retrieved
     */
    public function pushPostProcessor(string $class, array|Closure|null $parameters = null): self
    {
        $this->postProcessors->put($class, $parameters);

        return $this;
    }

    /**
     * Removes postProcessor
     */
    public function removePostProcessor(string $class): self
    {
        $this->postProcessors->forget($class);

        return $this;
    }

    /**
     * Runs the result for retrieval calls to the repository
     * through postprocessing.
     *
     * @param  Collection|Model|null $result the result of the query, ready for postprocessing
     * @return Model|Collection|mixed[]|null
     */
    public function postProcess(Collection|Model|null $result): Model|Collection|array|null
    {
        // determine whether there is anything to process
        if (is_null($result) || is_a($result, Collection::class) && $result->isEmpty()) {
            return $result;
        }

        // check if there is anything to do process it through
        if ($this->postProcessors->isEmpty()) {
            return $result;
        }

        // for each Model, instantiate and apply the processors
        if (is_a($result, Collection::class)) {
            $result->transform(fn($model) => $this->applyPostProcessorsToModel($model));
        } elseif (is_a($result, AbstractPaginator::class)) {
            // result is paginate() result
            // do not apply postprocessing for now (how would we even do it?)
            return $result;

        } else {
            // result is a model
            $result = $this->applyPostProcessorsToModel($result);
        }

        return $result;
    }

    /**
     * Applies the currently active postprocessors to a model
     */
    protected function applyPostProcessorsToModel(Model $model): Model
    {
        foreach ($this->postProcessors as $processorClass => $parameters) {

            // if a processor class was added as a value instead of a key, it
            // does not have parameters
            if (is_numeric($processorClass)) {
                $processorClass = $parameters;
                $parameters     = null;
            }

            $processor = $this->makePostProcessor($processorClass, $parameters);

            $model = $processor->process($model);
        }

        return $model;
    }

    /**
     * Method for making an post processor
     *
     * @param  string $processor
     * @param  mixed  $parameters flexible parameter input can be string, array or closure that generates either
     * @return PostProcessorInterface
     */
    protected function makePostProcessor(mixed $processor, $parameters = null)
    {
        // no parameters? simple make
        if (is_null($parameters)) {
            $parameters = [];
        } elseif (is_callable($parameters)) {
            $parameters = $parameters();
        }

        if ( ! is_array($parameters)) {

            $parameters = [ $parameters ];
        }


        /** @var PostProcessorInterface $instance */
        if ( ! empty($parameters)) {

            $reflectionClass = new ReflectionClass($processor);

            $instance = $reflectionClass->newInstanceArgs($parameters);

        } else {

            $instance = app($processor);
        }

        return $instance;
    }


    // -------------------------------------------------------------------------
    //      Attribute hiding
    // -------------------------------------------------------------------------

    /**
     * Unhide an otherwise hidden attribute (in $hidden array)
     *
     * Note that these count on only the model's 'hidden' array to be set,
     * if a model whitelists with visible, it won't work as expected
     */
    public function unhideAttribute(string $attribute): self
    {
        if (! in_array($attribute, $this->extraUnhidden)) {
            $this->extraUnhidden[] = $attribute;
        }

        return $this;
    }


    /**
     * Method for unhiding attributes.
     */
    public function unhideAttributes(array|Arrayable $attributes): self
    {
        if (! empty($attributes)) {
            foreach ($attributes as $attribute) {
                $this->unhideAttribute($attribute);
            }
        }

        return $this;
    }

    /**
     * Hide an otherwise visible attribute (in $hidden array)
     *
     * Note that these count on only the model's 'hidden' array to be set,
     * if a model whitelists with visible, it won't work as expected
     */
    public function hideAttribute(string $attribute): self
    {
        if (($key = array_search($attribute, $this->extraUnhidden)) !== false) {
            unset($this->extraUnhidden[ $key ]);
        } else {
            if ( ! in_array($attribute, $this->extraHidden)) {
                $this->extraHidden[] = $attribute;
            }
        }

        return $this;
    }

    public function hideAttributes(array|Arrayable $attributes): self
    {
        if ( ! empty($attributes)) {
            foreach ($attributes as $attribute) {
                $this->hideAttribute($attribute);
            }
        }

        return $this;
    }

    /**
     * Resets any hidden or unhidden attribute changes
     */
    public function resetHiddenAttributes()
    {
        $this->extraHidden   = [];
        $this->extraUnhidden = [];
    }


    // -------------------------------------------------------------------------
    //      Overrides for applying postprocessing
    // -------------------------------------------------------------------------

    /**
     * Override
     */
    public function first(array|null|string $columns = ['*']): ?Model
    {
        return $this->postProcess( parent::first($columns) );
    }

    /**
     * Override
     *
     * @throws ModelNotFoundException
     */
    public function firstOrFail(array $columns = ['*']): Model
    {
        return $this->postProcess( parent::firstOrFail($columns) );
    }

    /**
     * Override
     */
    public function all(array|string $columns = ['*']): \Illuminate\Database\Eloquent\Collection
    {
        return $this->postProcess( parent::all($columns) );
    }


    /**
     * {@inheritdoc}
     */
    public function paginate(?int $perPage = 1, array $columns = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginator
    {
        return $this->postProcess( parent::paginate($perPage, $columns, $pageName, $page) );
    }

    /**
     * {@inheritdoc}
     */
    public function find(mixed $id, array $columns = ['*'], ?string $attribute = null): ?Model
    {
        return $this->postProcess( parent::find($id, $columns, $attribute) );
    }

    /**
     * Override
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(int|string $id, array $columns = ['*']): Model
    {
        return $this->postProcess( parent::findOrFail($id, $columns) );
    }

    /**
     * Override
     */
    public function findBy(string $attribute, mixed $value, array $columns = ['*']): ?Model
    {
        return $this->postProcess( parent::findBy($attribute, $value, $columns) );
    }

    /**
     * Override
     */
    public function findAllBy(string $attribute, mixed $value, array $columns = ['*']): mixed
    {
        return $this->postProcess( parent::findAllBy($attribute, $value, $columns) );
    }

    /**
     * Override
     */
    public function findWhere(array|Arrayable $where, array $columns = ['*'], bool $or = false): ?Collection
    {
        return $this->postProcess( parent::findWhere($where, $columns, $or) );
    }


    /**
     * Applies callback to query for easier elaborate custom queries
     * on all() calls.
     *
     * The callback must be query/builder compatible.
     *
     * @throws InvalidArgumentException
     */
    public function allCallback(Closure $callback, array $columns = ['*']): Collection
    {
        return $this->postProcess( parent::allCallback($callback, $columns) );
    }

    /**
     * Applies callback to query for easier elaborate custom queries
     * on find (actually: ->first()) calls.
     *
     * The callback must be query/builder compatible.
     *
     * @throws InvalidArgumentException
     */
    public function findCallback(Closure $callback, array $columns = ['*']): Collection
    {
        return $this->postProcess( parent::findCallback($callback, $columns) );
    }
}
