<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Specialty;
use App\Transformers\SpecialtyTransformer;
use League\Fractal;
use League\Fractal\Manager;
use EllipseSynergie\ApiResponse\Contracts\Response;

class SpecialtyController extends Controller
{
    
    public function __construct(Response $response)
    {
        $this->response = $response;
        $this->middleware('ajax');
    }

    public function index()
    {
        $specialties = Specialty::all();

        return $this->response->withCollection(
            $specialties,
            new SpecialtyTransformer
        );
    }
}
