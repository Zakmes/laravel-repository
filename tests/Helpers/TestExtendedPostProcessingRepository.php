<?php
namespace Czim\Repository\Test\Helpers;

use Czim\Repository\ExtendedPostProcessingRepository;

class TestExtendedPostProcessingRepository extends ExtendedPostProcessingRepository
{
    // model needs an active check by default
    protected bool $hasActive = true;

    // test assumes cache is enabled by default
    protected bool  $enableCache = true;


    public function model(): string
    {
        return TestExtendedModel::class;
    }

}

