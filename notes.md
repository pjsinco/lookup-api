# Lookup API

###Order of seeding
1. Locations
2. Specialties
3. SpecialtySubspecialty
4. Aliases
5. SpecialtyAlias
6. Physicians

```
php artisan db:seed --class=LocationTableSeeder
php artisan db:seed --class=SpecialtyTableSeeder
php artisan db:seed --class=SpecialtySubspecialtyTableSeeder
php artisan db:seed --class=AliasTableSeeder
php artisan db:seed --class=CreateSpecialtyAliasTableSeeder
php artisan db:seed --class=PhysicianTableSeeder
```

###When things aren't working
```
php artisan cache:clear 
composer dump-autoload
```

###When we have Github permission problems on the server:
```
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_rsa 
```

### Notes

#####Thu Sep 24 06:38:10 2015 CDT
* Sitepoint: [Fractal: a Practical Walkthrough](http://www.sitepoint.com/fractal-practical-walkthrough/)

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
    > }
))>))}
```

* SO: [Laravel connection to sql server](http://stackoverflow.com/questions/23008924/laravel-connection-to-sql-server)

#####Thu Oct 22 16:46:35 2015 CDT
>Let’s call your current data table “F”
> 
>COPY the distinct address/city/state and lat lon fields from “F” to another table, call that table “LL”.
>    This should be doable in one mysql “copy table” or “create table as select…” or “insert as select …” statement.
> 
>Overwrite table “F” with the new/current data from the imis database
>    It’s simplest to just truncate (i.e., discard all data) and replace it in full.
>    You might consider creating the new table with a temporary name, then dropping the old table and renaming it only after you know you have the new data safe-and-sound.
> 
>Reset the lat lon fields in all records in “F” to null
> 
>For each record in “F” where address/city/state exists in LL, copy the latlon from LL to F.
>    Again, this should be doable in one sql update statement
> 
>For each record in “F” where lat lon is still blank, go get it from your service.
 
* StackEx: [Copy from one MySQL table to another MySQL table of same database](http://dba.stackexchange.com/questions/72042/copy-from-one-mysql-table-to-another-mysql-table-of-same-database)

#####Fri Oct 23 04:21:15 2015 CDT
* Github: [Geocod.io PHP](https://github.com/davidstanley01/geocodio-php)

#####Wed Oct 28 16:36:49 2015 CDT
* Manual refresh
    1. Export physicians table as CSV [source]](http://www.tech-recipes.com/rx/1475/save-mysql-query-results-into-a-text-or-csv-file/)
```sql
select id,full_name,prefix,first_name,middle_name,last_name,suffix,designation,SortColumn,MemberStatus,City,State_Province,Zip,Country,COLLEGE_CODE,YearOfGraduation,fellows,PrimaryPracticeFocusCode,PrimaryPracticeFocusArea,SecondaryPracticeFocusCode,SecondaryPracticeFocusArea,website,AOABoardCertified,address_1,address_2,Phone,Email,ABMS,Gender,CERT1,CERT2,CERT3,CERT4,CERT5,lat,lon,geo_confidence,geo_city,geo_state,geo_matches
from physicians  
into outfile '/tmp/physicians-2015-10-18-geocoded.csv' 
fields
     terminated by ','
     enclosed by '"'
lines
     terminated by '\n';"'
```
    2. Fix line-endings in TextMate
    3. On local: ```php artisan migrate:refresh --seed```
    4. scp the CSV file to production
    5. Run the migration on production

* SO: [Script to trim 7 columns to 5 ( csv file )](http://stackoverflow.com/questions/9814272/script-to-trim-7-columns-to-5-csv-file)

#####Thu Nov  5 10:38:49 2015 CST
* Query to get column names:
```sql
select `COLUMN_NAME`
from `INFORMATION_SCHEMA`.`COLUMNS`
where `table_schema` = 'findyourdo'
    and `table_name` = 'physicians'
```

* ```csvcut``` move
```
csvcut -c aoa_mem_id,full_name,prefix,first_name,middle_name,last_name,suffix,designation,SortColumn,MemberStatus,City,State_Province,Zip,Country,COLLEGE_CODE,YearOfGraduation,fellows,PrimaryPracticeFocusCode,PrimaryPracticeFocusArea,SecondaryPracticeFocusCode,SecondaryPracticeFocusArea,website,AOABoardCertified,address_1,address_2,Phone,Email,ABMS,Gender,CERT1,CERT2,CERT3,CERT4,CERT5,lat,lon,geo_confidence,geo_city,geo_state,geo_matches 
```

#####Wed Mar 30 16:31:59 2016 CDT
###Cors issue
* [Laracasts forum](https://laracasts.com/discuss/channels/requests/laravel-5-cors-headers-with-filters?page=4)
* Blog: [Laravel-5 REST API and CORS](https://blog.nikhilben.com/2015/09/02/laravel5-rest-api-and-cors/)
  * Looks like best thing to try first
* Packagist: [barryvdh/laravel-cors](https://packagist.org/packages/barryvdh/laravel-cors)

###### Mon Dec 18 11:56:36 2017 CST
* [The Comprehensive Guide to URL Parameter Encryption in PHP - Paragon Initiative Enterprises Blog](https://paragonie.com/blog/2015/09/comprehensive-guide-url-parameter-encryption-in-php)

###### Wed Apr 17 15:55:31 2019 CDT 
* [JonasCz/How-To-Prevent-Scraping: The ultimate guide on preventing Website Scraping](https://github.com/JonasCz/How-To-Prevent-Scraping)
