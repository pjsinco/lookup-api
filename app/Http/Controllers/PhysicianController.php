<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Transformers\PhysicianTransformer;
use App\Physician;
use League\Fractal;
use League\Fractal\Manager;

class PhysicianController extends Controller
{
    protected $manager;
    /**
     * Default search distance in miles.
     *
     */
    private $defaultDistance = 25;
    private $maxDistance = 250;

    /**
     * Sequence of fallback distances to use in trying to return
     * a nonempty result set.
     */
    private $fallbackDistances = [25, 50, 100, 250];

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Show a physician
     *
     * @return void
     * @author PJ
     */
    public function show($id)
    {
        
        $phys = Physician::findOrFail($id);
        $resource = new Fractal\Resource\Item($phys, new PhysicianTransformer);
        $output = $this->manager->createData($resource)->toArray();

        return $output;


        //return $this->respond([
            //'data' => $this->physicianTransformer->transform($physician)
        //]);
    }

}
