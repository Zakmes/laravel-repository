<?php declare(strict_types=1);

namespace Czim\Repository\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * The point of this is to provide Eloquent relations management through
 * some intermediate object (i.e. a Repository) to make model manipulation
 * easier to test/mock.
 */
trait HandlesEloquentRelationManipulation
{
    /**
     * Executes a sync on the model provided
     */
    public function sync(Model $model, string $relation, array $identifiers, bool $detaching = true): array
    {
        return $model->{$relation}()->sync($identifiers, $detaching);
    }

    /**
     * Executes an attach on the model provided
     */
    public function attach(Model $model, string $relation, int $id, array $attributes = [], bool $touch = true): bool
    {
        return $model->{$relation}()->attach($id, $attributes, $touch);
    }

    /**
     * Executes a detach on the model provided
     *
     * @internal param array $attributes
     */
    public function detach(Model $model, string $relation, array $identifiers = [], bool $touch = true): int
    {
        return $model->{$relation}()->detach($identifiers, $touch);
    }

    /**
     * Executes an associate on the model model provided
     */
    public function associate(Model $model, string $relation, Model|int|string $with): bool
    {
        return $model->{$relation}()->associate($with);
    }

    /**
     * Executes a dissociate on the model model provided
     */
    public function dissociate(Model $model, string $relation, mixed $from): bool
    {
        return $model->{$relation}()->dissociate($from);
    }

}
