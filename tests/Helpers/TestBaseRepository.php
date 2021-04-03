<?php

declare(strict_types=1);

namespace Czim\Repository\Test\Helpers;

use Czim\Repository\BaseRepository;

class TestBaseRepository extends BaseRepository
{
    public function model(): string
    {
        return TestSimpleModel::class;
    }
}
