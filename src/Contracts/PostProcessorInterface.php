<?php
namespace Czim\Repository\Contracts;

use Illuminate\Database\Eloquent\Model;

interface PostProcessorInterface
{
    /**
     * Applies processing to a single model
     */
    public function process(Model $model): Model;
}
