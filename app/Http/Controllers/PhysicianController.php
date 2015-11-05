<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Transformers\PhysicianTransformer;
use DB;
use App\Physician;
use App\Specialty;
use League\Fractal;
use League\Fractal\Manager;
use EllipseSynergie\ApiResponse\Contracts\Response;


class PhysicianController extends Controller
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
        $this->response = $response;
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
     * Search for a specialty. Returns an array representation of the
     * Specialty model, or false if it's not found.
     *
     * @param string
     * @return instance of Specialty, or false
     */
    private function getSpecialty($query)
    {
        $query = trim(strtolower($query));
        $specialty = Specialty::where('full', 'like', '%' . $query . '%')->get()->all();

        // figure out its parent
        if (count($specialty) > 1) {
            
        } 


        if (!empty($specialty)) {
            return $specialty;
        } 
        return false;
    }

    /**
     * Find the parent specialty for any specialty.
     *
     * @param Specialty 
     * @return string array - the parent specialties
     */
    private function getParentSpecialties(Specialty $specialty)
    {
        //select specialty_id from specialty_subspecialty where subspecialty_id = 'FOM';
        $parentSpecialties = DB::table('specialty_subspecialty')
            ->where('subspecialty_id', '=', $specialty->code)
            ->get();

        if ($parentSpecialties) {
            return array_map(function($item) {
                return $item->specialty_id;
            }, $parentSpecialties);
        }

        return false;
    }

    /**
     * Get all subspecialties for a parent specialty.
     *
     * @param Specialty $specialty - the parent specialty
     * @return string - comma-delimited, wrapped in parentheses
     *    ex.: ('HNS','OOP','OTA','OTL','OTR','PDO','RHI')
     */
    private function getSubspecialties(Specialty $specialty, $string = true)
    {
        // Make sure we're working with a parent specialty
        $subs = DB::table('specialty_subspecialty')
            ->addSelect('subspecialty_id')
            ->where('specialty_id', '=', $specialty->code)
            ->get();
        
        $subsArray = array_map(function($item) {
            return $item->subspecialty_id;
        }, $subs);

        return $subsArray;
    }

    /**
     * Find physicians who practice any of the subspecialties of the 
     * parent specialty.
     *
     * @param Request request
     * @param Specialty specialty - the parent specialty
     * @param string distance
     */
    private function searchWithParentSpecialty(Request $request,
        Specialty $specialty, $distance
    )
    {
        $orderBy = $request->has('order_by') ? $request->order_by : 'distance';
        $sort = $request->has('sort') ? $request->sort : 'asc';
        $limit = $request->has('per_page') ? $request->per_page : '25';

        $subspecialties = $this->getSubspecialties($specialty);
        $searchDistance = $request->distance ? $request->distance : $distance;
        //$haversineSelectStmt = $this->haversineSelect($request->lat, $request->lon);


        $physicians = Physician::withinRadius(
            $request->lat, 
            $request->lon, 
            $searchDistance
        )
            ->whereIn('PrimaryPracticeFocusCode', $subspecialties )
            ->orderBy($orderBy, $sort)
            ->paginate($limit);

        // Instead of returning an empty Collection, let's return false
        return $physicians;
    }

    /**
     * Find physicians who practice one specific specialty.
     *
     * @param Request request
     * @param Specialty specialty - the parent specialty
     * @param string distance
     */
    private function searchWithSubspecialty(Request $request,
        Specialty $specialty, $distance
    )
    {
        $orderBy = $request->has('order_by') ? $request->order_by : 'distance';
        $sort = $request->has('sort') ? $request->sort : 'asc';
        $limit = $request->has('per_page') ? $request->per_page : '25';

        
        $searchDistance = $request->distance ? $request->distance : $distance;
        //$haversineSelectStmt = $this->haversineSelect($request->lat, $request->lon);


         $physicians = Physician::withinRadius(
             $request->lat, 
             $request->lon, 
             $searchDistance
         )
         ->where('PrimaryPracticeFocusCode', '=', $specialty->code )
         ->orderBy($orderBy, $sort)
         ->paginate($limit);
    
        return $physicians;
    }

    /**
     * Search for physicians who practice a certain specialty.
     * 
     * @param Request $request
     * @param Specialty $specialty
     * @param int $distance
     * @return Array
     */
    private function searchWithSpecialty(Request $request, 
        Specialty $specialty, $distance)
    {
        if ($this->isParentSpecialty($specialty)) {
            $physicians = $this->searchWithParentSpecialty($request, $specialty, $distance);
        } else {
            $physicians = $this->searchWithSubspecialty($request, $specialty, $distance);
        }
    
        return $physicians;
    }

    /**
     * Search for physicians by first name, last name or specialty
     * 
     * @param Request $request
     * @param int $distance
     * @return Array
     */
    private function searchWithQuery(Request $request, $distance)
    {
        $orderBy = $request->has('order_by') ? $request->order_by : 'distance';
        $sort = $request->has('sort') ? $request->sort : 'asc';
        $limit = $request->has('per_page') ? $request->per_page : '25';

        $physicians = Physician::withinRadius(
            $request->lat, 
            $request->lon, 
            $distance
        )
        ->where('last_name', 'like', $request->q . '%' )
        ->orWhere('first_name', 'like', $request->q . '%' )
        ->orWhere('PrimaryPracticeFocusArea', 'like', $request->q . '%' )
        ->orderBy($orderBy, $sort)
        ->paginate($limit);

        return $physicians;
    }

    /**
     * Calcuate the number of physicians within a certain number of 
     * miles of a ZIP code.
     *
     * @param string
     * @return string
     */
    public function withinDistance(Request $request)
    {

        if (!$request->has(['miles', 'zip'])) {
            app()->abort(404);
        }

        $location = Location::where('zip', '=', $request->zip)
            ->get();
        $lat = $location[0]->lat;
        $lon = $location[0]->lon;
        //$haversineSelectStmt = 
            //$this->haversineSelect($lat, $lon);

        //$physicians = Physician::select(DB::raw($haversineSelectStmt))
            //->having('distance', '<', $request->miles)
            //->orderBy('distance', 'asc')
            //->get();
        $physicians = Physician::withinRadius($lat, $lon, $searchDistance)
            ->orderBy('distance', 'asc')
            ->paginate($limit);

        $count = (string)count($physicians);
        return json_encode(['count' => $count]);
    }

    /**
     * Generate the body of a SQL SELECT statement for
     * retrieving items within a radius if the given latitude, longitude
     * according to the Haversine formula.
     *
     * @param string 
     * @param string 
     * @return string
     */
    public function haversineSelect($lat, $lon) 
    {
        $haversineSelect  = "*, (3959 * acos( cos( radians(" . $lat;
        $haversineSelect .= ") ) * cos( radians( lat ) ) * ";
        $haversineSelect .= "cos( radians( lon ) - radians(" . $lon;
        $haversineSelect .= ") ) + sin( radians(" . $lat . ") ) ";
        $haversineSelect .= "* sin( radians( lat ) ) ) ) AS distance";

        return $haversineSelect;
    }

    /**
     * Fuzzy search for a physician by the beginning of first name or last name
     *
     * @param Request - the request
     * @return json
     */
    public function nameSearch(Request $request)
    {


        $physicians = Physician::withinRadius(
            $request->lat, 
            $request->lon, 
            $this->defaultDistance
        )
        ->where('last_name', 'like', $request->name . '%')
        ->orWhere('first_name', 'like', $request->name . '%')
        ->get();

        $queryMeta = [
            'city' => urldecode($request->city),
            'state' => $request->state,
            'zip' => $request->zip ? $request->zip : null,
            'specialty' => !empty($specialty) ? $specialty->full : null,
            'q' => $request->q,
            'count' => ($physicians ? count($physicians) : 0),
            'radius' => $this->defaultDistance
        ];

        if (!empty($physicians)) {
            return $this->response->withCollection(
                $physicians, 
                new PhysicianTransformer,
                null,
                null,
                $queryMeta
            );
        } 
    
        $errorMeta = [
            'meta' => $meta, 
            'error' => [
                'code' => 'GEN-NOT-FOUND',
                'http_code' => 404,
                'message' => 'Physician not found'
            ]
        ]; 
        return $this->response->withArray($errorMeta);

    }

    public function search(Request $request)
    {
        $searchDistance = $request->distance ? $request->distance : 0;

        $physicians = null;
        $specialty = null;
        $query = null;

        if ($request->has('code')) {

            $specialty = Specialty::where('code', '=', $request->code)->first();

        } elseif ($request->has('q') && !$request->has('code')) {

            //$physicians = $this->searchWithSpecialty

            $query = $request->q;
        }

        // if we don't have a requested distance, we'll cycle through
        // our fallback distances until we get at least 1 result;
        // if we don't have anything by our max distance, we'll return 0.
        if (!$request->has('distance')) {
            while (empty($physicians) || $physicians->isEmpty()) {
                $searchDistance = $this->getNextDistance($searchDistance);

                if ($specialty) {
                    $physicians = $this->searchWithSpecialty(
                        $request, 
                        $specialty, 
                        $searchDistance
                    );
                } elseif ($query) {
                    $physicians = $this->searchWithQuery($request, $searchDistance);
                } else {
                    //$haversineSelectStmt = 
                        //$this->haversineSelect($request->lat, $request->lon);

                    // General search
                        $orderBy = $request->has('order_by') ? $request->order_by : 'distance';
                        $sort = $request->has('sort') ? $request->sort : 'asc';
                        $limit = $request->has('per_page') ? $request->per_page : '25';

        
                        $physicians = Physician::withinRadius(
                            $request->lat, 
                            $request->lon, 
                            $searchDistance
                        )
                        ->where('last_name', 'like', $request->name . '%')
                        ->orWhere('first_name', 'like', $request->name . '%')
                        ->orderBy($orderBy, $sort)
                        ->paginate($limit);

                }

                if ($searchDistance == $this->maxDistance) {
                    break;
                } 
            }
        } else {
            // We have a distance, so use it
            if ($specialty) {
                $physicians = $this->searchWithSpecialty(
                        $request, 
                        $specialty, 
                        $request->distance
                );
            } elseif ($query) {
                $physicians = 
                    $this->searchWithQuery($request, $request->distance);
            } else {
                //$haversineSelectStmt = 
                    //$this->haversineSelect($request->lat, $request->lon);

                // General search, with distance
                $orderBy = $request->has('order_by') ? $request->order_by : 'distance';
                $sort = $request->has('sort') ? $request->sort : 'asc';
                $limit = $request->has('per_page') ? $request->per_page : '25';

        
                $physicians = Physician::withinRadius(
                    $request->lat, 
                    $request->lon, 
                    $searchDistance
                )
                ->where('last_name', 'like', $request->name . '%')
                ->orWhere('first_name', 'like', $request->name . '%')
                ->orderBy($orderBy, $sort)
                ->paginate($limit);
            }

        }

        $queryMeta = [
            'city' => urldecode($request->city),
            'state' => $request->state,
            'zip' => $request->zip ? $request->zip : null,
            'specialty' => !empty($specialty) ? $specialty->full : null,
            'q' => $request->q,
            'count' => ($physicians ? count($physicians) : 0),
            'radius' => $searchDistance
        ];

        if (!empty($physicians)) {
            return $this->response->withPaginator(
                $physicians, 
                new PhysicianTransformer,
                null,
                $queryMeta
            );
        } 
    
        $errorMeta = [
            'meta' => $meta, 
            'error' => [
                'code' => 'GEN-NOT-FOUND',
                'http_code' => 404,
                'message' => 'Physician not found'
            ]
        ]; 
        return $this->response->withArray($errorMeta);
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
        
        if ($physician) {
            return $this->response
                ->withItem($physician, new PhysicianTransformer);
        }

        $errorMeta = [
            'error' => [
                'code' => 'GEN-NOT-FOUND',
                'http_code' => 404,
                'message' => 'Physician not found'
            ]
        ]; 

        return $this->response->withArray($errorMeta);

        //$resource = new Fractal\Resource\Item($phys, new PhysicianTransformer);
        //$output = $this->manager->createData($resource)->toArray();

        //return $output;


        //return $this->respond([
            //'data' => $this->physicianTransformer->transform($physician)
        //]);
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
