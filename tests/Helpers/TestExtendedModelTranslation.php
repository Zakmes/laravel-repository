<?php

declare(strict_types=1);

namespace Czim\Repository\Test\Helpers;

use Illuminate\Database\Eloquent\Model;
use Watson\Rememberable\Rememberable;

class TestExtendedModelTranslation extends Model
{
    use Rememberable;

    protected $fillable = [
        'translated_string',
    ];

}
