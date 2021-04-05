<?php

declare(strict_types=1);

namespace Czim\Repository\Contracts;

interface HandlesListifyModelsInterface
{
    /**
     * Updates the position for a record using Listify
     * The new position is by default the top spot.
     */
    public function updatePosition(int $identifier, int $newPosition = 1): bool;
}
