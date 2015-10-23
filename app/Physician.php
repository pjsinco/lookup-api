<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

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
//        'prefix',
//        'first_name',
//        'middle_name',
//        'last_name',
//        'suffix',
//        'designation',
//        'City',
//        'State_Province',
//        'Zip',
//        'Country',
//        'PrimaryPracticeFocusCode',
//        'PrimaryPracticeFocusArea',
//        'SecondaryPracticeFocusCode',
//        'SecondaryPracticeFocusArea',
//        'website',
//        'address_1',
//        'address_2',
//        'Phone',
//        'Email',
    ];

    public $hidden = [
        'id',
        'aoa_mem_id',
    ];

    //const DISTANCE_UNIT_MILES = 69.0;

    public function scopeWithinRadius($query, $lat, $lon, $radius = 25)
    {
        $distanceUnit = 69.0;

        $haversineSelect  = "*, (3959 * acos( cos( radians(" . $lat;
        $haversineSelect .= ") ) * cos( radians( lat ) ) * ";
        $haversineSelect .= "cos( radians( lon ) - radians(" . $lon;
        $haversineSelect .= ") ) + sin( radians(" . $lat . ") ) ";
        $haversineSelect .= "* sin( radians( lat ) ) ) ) AS distance";

//        $haversine = sprintf(
//            '*, (%f * DEGREES(ACOS(COS(RADIANS(%f)) * COS(RADIANS(lat)) * '.
//            'COS(RADIANS(%f - lng)) + SIN(RADIANS(%f)) * ' .
//            'SIN(RADIANS(lat))))) AS distance',
//            $distanceUnit,
//            $lat,
//            $lon,
//            $lat
//        );

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

        $query
            ->from(DB::raw('(' . $subselect->toSql() . ') as d'))
            ->where('distance', '<=', $radius);

    }
}



