<?php

declare(strict_types=1);

namespace Czim\Repository\Test\Helpers;

use Czim\Repository\Criteria\NullCriteria;
use Czim\Repository\ExtendedRepository;
use Czim\Repository\Traits\FindsModelsByTranslation;
use Czim\Repository\Traits\HandlesEloquentRelationManipulation;
use Czim\Repository\Traits\HandlesEloquentSaving;
use Czim\Repository\Traits\HandlesListifyModels;
use Illuminate\Support\Collection;

class TestExtendedRepository extends ExtendedRepository
{
    use HandlesEloquentRelationManipulation;
    use HandlesEloquentSaving;
    use HandlesListifyModels;
    use FindsModelsByTranslation;

    // model needs an active check by default
    protected bool $hasActive = true;

    // test assumes cache is enabled by default
    protected bool $enableCache = true;


    public function model(): string
    {
        return TestExtendedModel::class;
    }


    public function defaultCriteria(): Collection
    {
        return collect([
            'TestDefault' => new NullCriteria(),
        ]);
    }
}


