<?php

declare(strict_types=1);

namespace Czim\Repository\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * The point of this is to provide Eloquent saving through some
 * intermediate object (i.e. a Repository) to make model manipulation
 * easier to test/mock.
 */
interface HandlesEloquentSavingInterface
{
    /**
     * Executes a save on the model provided
     */
    public function save(Model $model, array $options = []): bool;
}
