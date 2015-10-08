# Lookup API

#####Thu Sep 24 06:38:10 2015 CDT
* Sitepoint: [Fractal: a Practical Walkthrough](http://www.sitepoint.com/fractal-practical-walkthrough/)

#####Fri Sep 25 14:57:51 2015 CDT
* Run these commands when we have Github permission problems:
```
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_rsa 
```

#####Mon Oct  5 08:29:17 2015 CDT
* [Composite keys in Laravel](http://laravel.com/docs/5.0/schema#adding-indexes)

```php
$table->primary(['first', 'last']);
```

#####Thu Oct  8 04:22:13 2015 CDT
* SO: [How to use paginate() with a having() clause when column does not exist in table](http://stackoverflow.com/questions/19349397/how-to-use-paginate-with-a-having-clause-when-column-does-not-exist-in-table/20945960#20945960)

* Laracasts MB: [Any idea how to solve pagination issue with having clause?](https://laracasts.com/discuss/channels/general-discussion/any-idea-how-to-solve-pagination-issue-with-having-clause?page=0)
```php
const DISTANCE_UNIT_KILOMETERS = 111.045;
const DISTANCE_UNIT_MILES      = 69.0;

    /**
     * @param $query
     * @param $lat
     * @param $lng
     * @param $radius numeric
     * @param $units string|['K', 'M']
     */
    public function scopeNearLatLng($query, $lat, $lng, $radius = 10, $units = 'K')
    {
        $distanceUnit = $this->distanceUnit($units);

        if (!(is_numeric($lat) && $lat >= -90 && $lat <= 90)) {
            throw new Exception("Latitude must be between -90 and 90 degrees.");
        }

        if (!(is_numeric($lng) && $lng >= -180 && $lng <= 180)) {
            throw new Exception("Longitude must be between -180 and 180 degrees.");
        }

        $haversine = sprintf('*, (%f * DEGREES(ACOS(COS(RADIANS(%f)) * COS(RADIANS(lat)) * COS(RADIANS(%f - lng)) + SIN(RADIANS(%f)) * SIN(RADIANS(lat))))) AS distance',
            $distanceUnit,
            $lat,
            $lng,
            $lat
        );

        $subselect = clone $query;
        $subselect
            ->selectRaw(DB::raw($haversine));

        // Optimize the query, see details here:
        // http://www.plumislandmedia.net/mysql/haversine-mysql-nearest-loc/

        $latDistance      = $radius / $distanceUnit;
        $latNorthBoundary = $lat - $latDistance;
        $latSouthBoundary = $lat + $latDistance;
        $subselect->whereRaw(sprintf("lat BETWEEN %f AND %f", $latNorthBoundary, $latSouthBoundary));

        $lngDistance     = $radius / ($distanceUnit * cos(deg2rad($lat)));
        $lngEastBoundary = $lng - $lngDistance;
        $lngWestBoundary = $lng + $lngDistance;
        $subselect->whereRaw(sprintf("lng BETWEEN %f AND %f", $lngEastBoundary, $lngWestBoundary));

        $query
            ->from(DB::raw('(' . $subselect->toSql() . ') as d'))
            ->where('distance', '<=', $radius);
    }

    /**
     * @param $units
     */
    private function distanceUnit($units = 'K')
    {
        if ($units == 'K') {
            return static::DISTANCE_UNIT_KILOMETERS;
        } elseif ($units == 'M') {
            return static::DISTANCE_UNIT_MILES;
        } else {
            throw new Exception("Unknown distance unit measure '$units'.");
        }
    }
))>))}
```
