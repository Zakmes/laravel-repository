<?php

declare(strict_types=1);

namespace Czim\Repository\Criteria\Common;

use Czim\Repository\Criteria\AbstractCriteria;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class Has extends AbstractCriteria
{
    public function __construct(
        protected string $relation,
        protected string $operator = '>=',
        protected int $count = 1,
        protected string $boolean = 'and',
        protected ?Closure $callback = null
    ) {}


    /**
     * @param Builder $model
     * @return mixed
     */
    public function applyToQuery($model)
    {
        return $model->has($this->relation, $this->operator, $this->count, $this->boolean, $this->callback);
    }

}
