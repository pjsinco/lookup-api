<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Transformers\PhysicianTransformer;
use Elit\DoctorHandler;
use Elit\SearchHelper;
use Elit\AggregateReporter;
use DB;
use App\Physician;
use App\Specialty;
use App\Alias;
use App\Location;
use League\Fractal;
use League\Fractal\Manager;
use EllipseSynergie\ApiResponse\Contracts\Response;
use Illuminate\Support\Str;

class DoctorController extends Controller
{
  protected $response;
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

  public function __construct(Response $response)
  {
    $this->middleware('ajax', ['only' => ['search', 'show']]);
    $this->response = $response;
  }

  /**
   * Search for physicians.
   *
   * @return JSON
   */
  public function search(Request $request)
  {
    $physicians = null;
    $searchDistance = $request->distance ? $request->distance : 0;
    $coords = $this->getCoordinates($request);
    $orderBy = $request->has('order_by') ? $request->order_by : 'distance';
    $sort = $request->has('sort') ? $request->sort : 'asc';
    $limit = $request->has('per_page') ? $request->per_page : '25';

    // if we don't have a requested distance, we'll cycle through
    // our fallback distances until we get at least 1 result;
    // if we don't have anything by our max distance, we'll return 0.
    if (!$request->has('distance')) {
      while (!$physicians || $physicians->count() == 0) {
        $searchDistance = $this->getNextDistance($searchDistance);

        $physicians = Physician::withinDistance(
          $coords['lat'], 
          $coords['lon'], 
          $searchDistance
          )
          ->alias($request->alias_id)
          ->name($request->q) 
          ->gender($request->gender);

        if ($searchDistance == $this->maxDistance) {
          break;
        } 

      }
    } else {
      $physicians = Physician::withinDistance(
        $coords['lat'], 
        $coords['lon'], 
        $searchDistance
        )
      ->alias($request->alias_id)
      ->name($request->q) 
      ->gender($request->gender);
     }

    $alias = Alias::find($request->alias_id);

    $queryMeta = [
      'city' => Str::title(urldecode($request->city)),
      'state' => mb_strtoupper($request->state),
      'zip' => $request->zip ? 
        $request->zip : $this->getZip($request->city, $request->state),
      //'alias_id' => $request->alias_id ? $request->alias_id : null,
      'alias' => $alias ? $alias->alias : null,
      'alias_id' => $alias ? $alias->id : null,
      'aggregate' => AggregateReporter::report($physicians, $request->alias_id),
      'q' => $request->q,
      'gender' => $request->gender,
      'count' => ($physicians ? $physicians->count() : 0),
      'radius' => $searchDistance,
      'order_by' => $request->order_by,
      'sort' => $request->sort,
      'center' => [
        'lat' => $coords['lat'],
        'lon' => $coords['lon'],
      ],
    ];

    $physicians = $physicians->orderBy($orderBy, $sort)
      ->paginate($limit)
      ->appends($request->query());


    return $this->response->withPaginator(
      $physicians,
      new PhysicianTransformer,
      null,
      $queryMeta
      );
  }

  /**
   * Show a physician
   *
   * @return void
   * @author PJ
   */
  public function show($id)
  {
    $physician = Physician::find($id);

    return $this->response
      ->withItem($physician, new PhysicianTransformer);
  }

  /**
   * Get the next distance to try.
   * Ex.: 33 is passed in, return 50.
   * Ex.: 100 is passed in, return 250.
   *
   * @param int
   * @return int
   */
  private function getNextDistance($queriedDistance = 0)
  {
    foreach ($this->fallbackDistances as $distance) {
    if ($distance > $queriedDistance) {
      return $distance;
    }
    }

    // the queriedDistance is already beyond our distances
    return $queriedDistance;
  }

  /**
   * Get all aliases for a specialty.
   *
   * @param string $specialtyCode - the parent specialty code (ex.: 'AJI')
   * @return array of IDs
   *    ex.: [80, 5, 35]
   */
  private function getAliases($specialtyCode)
  {
    $aliasIds = DB::Table('specialty_alias')
    ->addSelect('alias_id')
    ->where('specialty_id', '=', $specialty)->get();
  }

  /**
   * Find coordinates for a location, based on zip or city and state.
   *
   */
  public function getCoordinates(Request $request) 
  {
    if ($request->has('zip')) {
      $location = Location::where('zip', '=', $request->zip)
        ->get();
      $coords['lat'] = $location[0]->lat;
      $coords['lon'] = $location[0]->lon;
      return $coords;
    }

    $location = Location::where('city', '=', $request->city)
      ->where('state', '=', $request->state)
      ->get();

  
    $filtered = $location->filter(function($item) {  // use the city center entry, which has no zip
        return $item['zip'] == ''; 
      })
      ->first();

    if (!$filtered) {
      $location = $location->first();
      $coords['lat'] = $location->lat;
      $coords['lon'] = $location->lon;
    } else {
      $coords['lat'] = $filtered->lat;
      $coords['lon'] = $filtered->lon;
    }

    return $coords;
  }

  public function getZip($city, $state) 
  {
    return Location::where('city', '=', $city)
    ->where('state', '=', $state)
    ->first()->zip;
  }

  /**
   * Determine whether a specialty is a parent specialty or a subspecialty.
   *
   * @param Specialty
   * @return boolean
   */
  private function isParentSpecialty(Specialty $specialty)
  {
    $result = DB::table('specialty_subspecialty')
    ->where('specialty_id', '=', $specialty->code)
    ->get();

    return !empty($result);
  }
}
