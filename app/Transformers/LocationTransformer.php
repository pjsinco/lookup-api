<?php

namespace App\Transformers;

use App\Location;
use League\Fractal;
use League\Fractal\TransformerAbstract;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

class LocationTransformer extends TransformerAbstract
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
    public function transform(Location $location)
    {
        return [
            'zip' => $location['zip'],
            'city' => $location['city'],
            'state' => $location['state'],
            'lat' => $location['lat'],
            'lon' => $location['lon'],
        ];
    }

}
