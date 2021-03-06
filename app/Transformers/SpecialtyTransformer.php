<?php

namespace App\Transformers;

use App\Specialty;
use League\Fractal;
use League\Fractal\TransformerAbstract;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

class SpecialtyTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var  array
     */
    protected $availableIncludes = [];

    /**
     * List of resources to automatically include
     *
     * @var  array
     */
    protected $defaultIncludes = [];

    /**
     * Transform object into a generic array
     *
     * @var  object
     */
    public function transform(Specialty $specialty)
    {
        return [
            'code' =>  $specialty['code'],
            'name' => $specialty['full'],
            'is_parent' => $specialty['is_parent'],
        ];

    }
}
