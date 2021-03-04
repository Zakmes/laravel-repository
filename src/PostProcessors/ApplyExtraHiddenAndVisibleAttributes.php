<?php
namespace Czim\Repository\PostProcessors;

use Czim\Repository\Contracts\PostProcessorInterface;
use Illuminate\Database\Eloquent\Model;

class ApplyExtraHiddenAndVisibleAttributes implements PostProcessorInterface
{
    public function __construct(
        protected array $hidden,   // List of fields to make sure are hidden on the model.
        protected array $unhidden, // List of fields to make sure are visible on the model.
    ) {}

    /**
     * Applies processing to a single model
     */
    public function process(Model $model): Model
    {
        $hiddenOnModel = $model->getHidden();

        foreach ($this->unhidden as $unhidden) {
            if (($key = array_search($unhidden, $hiddenOnModel)) !== false) {
                unset($hiddenOnModel[ $key ]);
            }
        }

        $hiddenOnModel = array_merge($hiddenOnModel, $this->hidden);

        $model->setHidden($hiddenOnModel);

        return $model;
    }
}
