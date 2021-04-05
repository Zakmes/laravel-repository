<?php

declare(strict_types=1);

namespace Czim\Repository\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * The point of this is to provide Eloquent saving through some
 * intermediate object (i.e. a Repository) to make model manipulation
 * easier to test/mock.
 */
trait HandlesEloquentSaving
{
    /**
     * Executes a save on the model provided
     */
    public function save(Model $model, array $options = []): bool
    {
        return $model->save($options);
    }

}
