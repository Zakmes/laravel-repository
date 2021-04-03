<?php

declare(strict_types=1);

namespace Czim\Repository\Criteria\Common;

use Czim\Repository\Criteria\AbstractCriteria;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class WhereHas extends AbstractCriteria
{
    public function __construct(
        protected string $relation,
        protected Closure $callback,
        protected string $operator = '>=',
        protected int $count = 1
    ) {}


    /**
     * @param Builder $model
     * @return mixed
     */
    public function applyToQuery($model)
    {
        return $model->whereHas($this->relation, $this->callback, $this->operator, $this->count);
    }

}
