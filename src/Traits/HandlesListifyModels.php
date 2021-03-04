<?php
namespace Czim\Repository\Traits;

use Czim\Listify\Contracts\ListifyInterface;
use Illuminate\Database\Eloquent\Model;

trait HandlesListifyModels
{
    /**
     * Updates the position for a record using Listify
     * The new position is by default the top spot.
     */
    public function updatePosition(int $id, int $newPosition = 1): bool|Model
    {
        $model = $this->makeModel(false);

        if (! ($model = $model->find($id))) {
            return false;
        }

        $this->checkModelHasListify($model);

        /** @var ListifyInterface $model */
        $model->setListPosition( (int) $newPosition );

        return $model;
    }

    /**
     * Checks whether the given model has the Listify trait
     */
    protected function checkModelHasListify(Model $model): void
    {
        // should be done with a real interface, but since that is not provided
        // with Listify by default, check only for the methods used here
        // ( ! is_a($model, ListifyInterface::class))

        if (! method_exists($model, 'setListPosition')) {
            throw new \InvalidArgumentException('Method can only be used on Models with the Listify trait');
        }
    }
}
