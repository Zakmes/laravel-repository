<?php

declare(strict_types=1);

namespace Czim\Repository\Contracts;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface PostProcessingRepositoryInterface
{
    /**
     * Returns the default list of postprocessors to apply to models
     * before returning the result to anything using the repository.
     *
     * Each entry is combination of a key (the classname of the postprocessor)
     * and a value (set of parameters, or Closure that generates parameters).
     *
     * The idea is that on each call, the postprocessors are instantiated,
     * and the parameters (if any) set for them, so any updates on the
     * repository are reflected by the processors.
     */
    public function defaultPostProcessors(): Collection|PostProcessorInterface;

    /**
     * Restores postprocessors to default collection
     */
    public function restoreDefaultPostProcessors(): self;

    /**
     * Pushes a postProcessor to apply to all models retrieved
     */
    public function pushPostProcessor(string $class, array|Closure|null $parameters = null): self;

    /**
     * Removes postProcessor
     */
    public function removePostProcessor(string $class): self;

    /**
     * Runs the result for retrieval calls to the repository
     * through postprocessing.
     */
    public function postProcess(Collection|Model|null $result): Model|Collection|array|null;

    /**
     * Unhide an otherwise hidden attribute (in $hidden array)
     *
     * Note that these count on only the model's 'hidden' array to be set,
     * if a model whitelists with visible, it won't work as expected
     */
    public function unhideAttribute(string $attribute): self;

    /**
     * Method for unhiding attributes.
     */
    public function unhideAttributes(array|Arrayable $attributes): self;

    /**
     * Hide an otherwise visible attribute (in $hidden array)
     *
     * Note that these count on only the model's 'hidden' array to be set,
     * if a model whitelists with visible, it won't work as expected
     */
    public function hideAttribute(string $attribute): self;

    /**
     * Method for hiding attributes.
     */
    public function hideAttributes(array|Arrayable $attributes): self;

    /**
     * Resets any hidden or unhidden attribute changes
     */
    public function resetHiddenAttributes();
}
