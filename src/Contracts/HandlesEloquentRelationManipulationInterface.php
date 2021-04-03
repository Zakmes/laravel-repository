<?php

declare(strict_types=1);

namespace Czim\Repository\Contracts;

use Illuminate\Database\Eloquent\Model;

interface HandlesEloquentRelationManipulationInterface
{
    /**
     * Executes a sync on the model provided
     */
    public function sync(Model $model, string $relation, array $ids, bool $detaching = true);

    /**
     * Executes an attach on the model provided
     */
    public function attach(Model $model, string $relation, int $id, array $attributes = [], bool $touch = true);

    /**
     * Executes a detach on the model provided
     *
     * @internal param array $attributes
     */
    public function detach(Model $model, string $relation, array $ids = [], bool $touch = true);

    /**
     * Executes an associate on the model model provided
     */
    public function associate(Model $model, string $relation, mixed $with): bool;

    /**
     * Executes a dissociate on the model model provided
     */
    public function dissociate(Model $model, string $relation, mixed $from): bool;
}
