<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
use App\Alias;
use Elit\SearchHelper;
use Elit\DoctorHandler;

class Physician extends Model
{
  //public $timestamps = false;
  //protected $primaryKey = 'aoa_mem_id';

  protected $fillable = [
    'aoa_mem_id',
    'full_name',
    'prefix',
    'first_name',
    'middle_name',
    'last_name',
    'suffix',
    'designation',
    'SortColumn',
    'MemberStatus',
    'City',
    'State_Province',
    'Zip',
    'Country',
    'COLLEGE_CODE',
    'YearOfGraduation',
    'fellows',
    'PrimaryPracticeFocusCode',
    'PrimaryPracticeFocusArea',
    'SecondaryPracticeFocusCode',
    'SecondaryPracticeFocusArea',
    'website',
    'AOABoardCertified',
    'address_1',
    'address_2',
    'Phone',
    'Email',
    'ABMS',
    'Gender',
    'CERT1',
    'CERT2',
    'CERT3',
    'CERT4',
    'CERT5',
    'lat',
    'lon',
    'geo_confidence',
    'geo_city',
    'geo_state',
    'geo_matches',
    'alias_1',
    'alias_2',
    'alias_3',
  ];

  public $hidden = [
    'id',
    'aoa_mem_id',
  ];

  //const DISTANCE_UNIT_MILES = 69.0;
  public function scopeWithinDistance($query, $lat, $lon, $radius = 25.0)
  {
    $distanceUnit = 69.0;

    $haversineSelect  = "*, (3959 * acos( cos( radians(" . $lat;
    $haversineSelect .= ") ) * cos( radians( lat ) ) * ";
    $haversineSelect .= "cos( radians( lon ) - radians(" . $lon;
    $haversineSelect .= ") ) + sin( radians(" . $lat . ") ) ";
    $haversineSelect .= "* sin( radians( lat ) ) ) ) AS distance";


    $subselect = clone $query;
    $subselect->selectRaw(DB::raw($haversineSelect));

    return $query
      ->from(DB::raw('(' . $subselect->toSql() . ') as d'))
      ->where('distance', '<=', $radius);
  }

  public function scopeWithinRadius($query, $lat, $lon, $radius = 25.0)
  {
    $distanceUnit = 69.0;

    $haversineSelect  = "*, (3959 * acos( cos( radians(" . $lat;
    $haversineSelect .= ") ) * cos( radians( lat ) ) * ";
    $haversineSelect .= "cos( radians( lon ) - radians(" . $lon;
    $haversineSelect .= ") ) + sin( radians(" . $lat . ") ) ";
    $haversineSelect .= "* sin( radians( lat ) ) ) ) AS distance";


    $subselect = clone $query;
    $subselect->selectRaw(DB::raw($haversineSelect));

    $latDistance = $radius / $distanceUnit;
    $latNorthBoundary = $lat - $latDistance;
    $latSouthBoundary = $lat + $latDistance;
    $subselect->whereRaw(
      sprintf('lat between %f and %f', $latNorthBoundary, $latSouthBoundary)
    );

    $lonDistance = $radius / $distanceUnit;
    $lonEastBoundary = $lon - $lonDistance;
    $lonWestBoundary = $lon + $lonDistance;
    $subselect->whereRaw(
      sprintf('lon between %f and %f', $lonEastBoundary, $lonWestBoundary)
    );

//dd(    $query
//      ->from(DB::raw('(' . $subselect->toSql() . ') as d'))
//      ->where('distance', '<=', $radius)
//      ->toSql());
    return $query
      ->from(DB::raw('(' . $subselect->toSql() . ') as d'))
      ->where('distance', '<=', $radius);
  }

  public function scopeGender($query, $gender) 
  {
    if (empty($gender)) { 
      return $query; 
    }

    return $query->where('Gender', '=', $gender);
  }

  public function scopeSpecialty($query, $code)
  {
    return $query->where('PrimaryPracticeFocusCode', '=', $code);
  }

  public function scopeName($query, $searchTerm) 
  {
    if (empty($searchTerm)) { 
      return $query; 
    }

    $stripped = DoctorHandler::stripDoctor(urldecode($searchTerm));

    if (SearchHelper::hasTwoWords($stripped)) {
      $nameArray = SearchHelper::getAsTwoWordArray($stripped);
      // Perform a first_name, last_name search ...
      return $query->where(function($query) use ($nameArray) {
        $query
          ->where('first_name', 'like', $nameArray[0] . '%' )
          ->Where('last_name', 'like', $nameArray[1] . '%' );
      });
    } else {
      return $query->where(function($query) use ($stripped) {
          $query
            ->where('last_name', 'like', $stripped . '%' )
            ->orWhere('first_name', 'like', $stripped . '%');
      });
    }
  }

  public function scopeAlias($query, $alias_id)
  {
    if (empty($alias_id)) {
      return $query;
    }

    $specialtiesArray = DB::Table('specialty_alias')
      ->addSelect('specialty_id')
      ->where('alias_id', '=', $alias_id)
      ->get();

    $specialties = array_map(
      function($specialty) { 
        return $specialty->specialty_id;
      }, 
      $specialtiesArray
    );

    // Search both primary and secondary specialties
    return $query->where(function($query) use ($specialties) {
      $query->whereIn('PrimaryPracticeFocusCode', $specialties)
            ->orWhereIn('SecondaryPracticeFocusCode', $specialties);
    });

    //return $query->whereIn('PrimaryPracticeFocusCode', $specialties);
    
  }
}



