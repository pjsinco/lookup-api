<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App;
use DB;
use Input;
use League\Fractal;
use League\Fractal\Manager;
use App\Transformers\LocationTransformer;
use EllipseSynergie\ApiResponse\Contracts\Response;


class LocationController extends Controller
{
    
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Split string on comma.
     *
     * @param string
     * @return array
     * @author PJ
     */
    private function splitLocation($location)
    {
        return explode(',', $location);
    }

    /**
     * Determine whether a string has a comma.
     *
     * @param string
     * @return boolean
     * @author PJ
     */
    private function hasComma($string)
    {
        return mb_strpos($string, ',') > 0;
    }

    /**
     * Get locations based on Zip code.
     *
     * @param string
     * @return json
     */
    public function searchByZip(Request $request)
    {
        $locations = App\Location::where('zip', '=', $request->q)
            ->get();

        if (! $locations->isEmpty()) {
            return $this->response->withCollection(
                $locations,
                new LocationTransformer
            );
        }

        $errorMeta = [
            'error' => [
                'code' => 'GEN-NOT-FOUND',
                'http_code' => 404,
                'message' => 'Location not found'
            ]
        ]; 

        return $this->response->withArray($errorMeta);

    }

    public function search(Request $request)
    {
        $location = $request->q;

        // if we have a comma, split the string on it
        if ($this->hasComma($location)) {
            $locationSplit = $this->splitLocation($location);
            $city = trim($locationSplit[0]);
            $state = trim($locationSplit[1]);

            $locations = App\Location::where('city', '=', $city)
                ->where('state', 'like', $state. '%')
                ->get();
        } else {
            $locations = App\Location::where('zip', 'like', $location . '%')
                ->orWhere('city', 'like', $location . '%')
                ->groupBy(['city', 'zip'])
                ->get();
        }
        
        if (! $locations->isEmpty()) {
            return $this->response->withCollection(
                $locations,
                new LocationTransformer
            );
        }

        $errorMeta = [
            'error' => [
                'code' => 'GEN-NOT-FOUND',
                'http_code' => 404,
                'message' => 'Location not found'
            ]
        ]; 

        return $this->response->withArray($errorMeta);
    }

    /**
     * Return a random location
     *
     * @return App\Location
     * @author PJ
     */
    public function random(Request $request)
    {
        // TODO disabling ajax detection
        // Doesn't seem to work when making calls from another server,
        // or at least localhost

        //if ($request->ajax()) {
            $location = App\Location::random();
            if ($location) {
                return $this->response->withItem(
                    $location,
                    new LocationTransformer
                );
            }
        //}

        $errorMeta = [
            'error' => [
                'code' => 'GEN-NOT-FOUND',
                'http_code' => 404,
                'message' => 'Location not found'
            ]
        ]; 

        return $this->response->withArray($errorMeta);
    }

    /**
     * Search for a location by city or zip
     *
     * @param string
     * @return json
     * @author PJ
     */
    public function show($location)
    {
        /**
         * EXAMPLE 
         * 
         * select *
         * from `locations`
         * where `zip` like 'naper%' or `city` like 'naper%'
         * group by city, zip
         */

        $locations = App\Location::where('zip', 'like', $location . '%')
            ->orWhere('city', 'like', $location . '%')
            ->groupBy(['city', 'zip'])
            ->get();
        
        if (! $locations->isEmpty()) {
            return $this->response->withCollection(
                $locations,
                new LocationTransformer
            );
        }

        $errorMeta = [
            'error' => [
                'code' => 'GEN-NOT-FOUND',
                'http_code' => 404,
                'message' => 'Location not found'
            ]
        ]; 

        return $this->response->withArray($errorMeta);
    }

    public function withinDistance(Request $request)
    {
        $q =
        "SELECT 
            city, 
            state, 
            (3959 * acos( cos( radians(" . $request->lat  . ") ) * cos( radians( lat ) ) * cos( radians( lon ) - radians(" . $request->lon . ") ) + sin( radians(" . $request->lat . ") ) * sin( radians( lat ) ) ) ) AS distance
        from locations
            having distance < $request->distance
        order by distance ASC";
        
        $locations = DB::select($q);
    }


    public function tryThisOne()
    {
        $location = (array) App\Location::random();
        return view('locations.try-this', ['location' => $location]);
    }
}
