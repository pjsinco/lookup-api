<?php

namespace Elit;

use Illuminate\Database\Schema\Blueprint;
use DB;
use Log;
use Schema;
use Stanley\Geocodio\Client;

/**
 * Update Find Your DO data with data from iMis.
 */
class RefreshFromImis
{

    private static $tempLocationTable = 'temp_locations';

    /**
     * 
     */
    public function __construct()
    {
        
    }

    /**
     * Get count of rows in source database.
     *
     */
    public static function getRowCount()
    {
        $db = self::getConnection();

        try {
            $q = "
                SELECT count(*)
                FROM imis.dbo.vFindYourDO 
                WHERE country = 'USA'
                    and not (first_name like '%test%' or 
                        first_name like '%AOA%'
                    )
            ";

            $stmt = $db->prepare($q);

            if ($stmt) {
                $stmt->execute();
                return $stmt->fetchColumn();
            }

        } catch (\PDOException $e) {
            Log::error($e->getMessage());

        } finally {
            $db = null;
        }
    }

    public static function getConnection()
    {
        $user = env('MSSQL_USERNAME');
        $password = env('MSSQL_PASSWORD');

        $db = new \PDO(
            'dblib:host=sql05-1.aoanet.local;dbname=imis', 
            $user, 
            $password
        );

        return $db;
    }

    public static function getRows()
    {
        try {

            $db = self::getConnection();

            $q = "
                SELECT * 
                FROM imis.dbo.vFindYourDO 
                WHERE country = 'USA'
                    and not (first_name like '%test%' or 
                        first_name like '%AOA%'
                    )
                ORDER BY id
            ";

            $stmt = $db->prepare($q);

            if ($stmt) {
                $stmt->execute();
            }
        

            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $db = null;

            return $results;

        } catch (\PDOException $e) {
            Log::error($e->getMessage());
            $db = null;
        }
    }

    public static function addRow($row)
    {
        try {
            DB::table('imis_raw')
                ->insert($row);
        } catch (\PDOException $e) {
            Log::error($e->getMessage());
        }
    }
    
    public static function truncateImisTable()
    {
        DB::table('imis_raw')->truncate();
    }

    public static function dropTempLocationTable()
    {
        return DB::statement("drop table if exists " . self::$tempLocationTable);
    }

    public static function createTempLocationTable()
    {
        self::dropTempLocationTable();

        $q = "
            CREATE TABLE IF NOT EXISTS " . self::$tempLocationTable . "(
                City VARCHAR(255),
                State_Province VARCHAR(16),
                Zip VARCHAR(16),
                address_1 VARCHAR(255),
                address_2 VARCHAR(255),
                lat FLOAT(10, 6),
                lon FLOAT(10, 6),
                geo_confidence VARCHAR(255),
                geo_city VARCHAR(255),
                geo_state VARCHAR(255),
                geo_matches BOOLEAN
            );
        ";

        $result = DB::statement($q);

        return $result;
    }

    public static function populateTempLocationTable()
    {
        $q = "
            INSERT INTO " . self::$tempLocationTable . "
                SELECT City, State_Province, Zip, address_1, address_2, 
                    lat, lon, geo_confidence, geo_city, geo_state, geo_matches
                FROM physicians;
        ";

        $result = DB::statement($q);

        return $result;
    }

    public static function truncatePhysiciansTable()
    {
        DB::table('physicians')->truncate();
    }

    public static function backupPhysiciansTable()
    {

        $backup = 'physicians_backup';

        if (Schema::hasTable($backup)) {
            Schema::drop($backup);
        }

        Schema::create($backup, function (Blueprint $table) {
            $table->timestamps();
            $table->increments('id');
            $table->string('aoa_mem_id', 16);
            $table->string('full_name');
            $table->string('prefix', 24)->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix', 24)->nullable();
            $table->string('designation', 24);
            $table->string('SortColumn');
            $table->string('MemberStatus', 48);
            $table->string('City');
            $table->string('State_Province', 16);
            $table->string('Zip', 16);
            $table->string('Country');
            $table->string('COLLEGE_CODE');
            $table->string('YearOfGraduation', 16);
            $table->string('fellows')->nullable();
            $table->string('PrimaryPracticeFocusCode', 16);
            $table->string('PrimaryPracticeFocusArea');
            $table->string('SecondaryPracticeFocusCode', 16)->nullable();
            $table->string('SecondaryPracticeFocusArea')->nullable();
            $table->string('website')->nullable();
            $table->boolean('AOABoardCertified');
            $table->string('address_1');
            $table->string('address_2')->nullable();
            $table->string('Phone', 16)->nullable();
            $table->string('Email')->nullable();
            $table->boolean('ABMS');
            $table->char('Gender', 1);
            $table->string('CERT1')->nullable();
            $table->string('CERT2')->nullable();
            $table->string('CERT3')->nullable();
            $table->string('CERT4')->nullable();
            $table->string('CERT5')->nullable();
            $table->float('lat', 10, 6);
            $table->float('lon', 10, 6);
            $table->string('geo_confidence');
            $table->string('geo_city');
            $table->string('geo_state');
            $table->boolean('geo_matches');
        });

        $q = "
            insert into $backup
                select * from physicians
        ";

        return DB::statement($q);
    }

    public static function getImisRawRows()
    {
        return DB::select(DB::raw('select * from imis_raw'));
    }

    public static function parseGeoData($geoDataRaw)
    {
        if (count($geoDataRaw->response->results) == 0) {
            return false;
        } 

        $data = new \StdClass(); 
        $data->lat = $geoDataRaw->response->results[0]->location->lat;
        $data->lon = $geoDataRaw->response->results[0]->location->lng;
        $data->geo_city = 
            $geoDataRaw->response->results[0]->address_components->city;
        $data->geo_state = 
            $geoDataRaw->response->results[0]->address_components->state;
        $data->geo_confidence = $geoDataRaw->response->results[0]->accuracy;
        $data->geo_matches = 0;

        return $data;
    }

    public static function geolocate($physician)
    {
        Log::notice('Fetching geolocation data for ' . $physician->full_name);

        $client = new Client(env('GEOCODIO_KEY'));
        $data = sprintf(
            '%s, %s, %s %s',
            $physician->address_1,
            $physician->City,
            $physician->State_Province,
            $physician->Zip
        );

        try {
            $geoDataRaw = $client->get($data);
            $geoData = self::parseGeoData($geoDataRaw);

            return $geoData;
        } catch (GeocodioAuthError $gae) {
            Log::warning('Error geolocating ' . $physician['full_name'] . 
                ': ' . $gae->getMessage());
        } catch (GeocodioDataError $gde) {
            Log::warning('Error geolocating ' . $physician['full_name'] . 
                ': ' . $gae->getMessage());
        } catch (GeocodioServerError $gse) {
            Log::warning('Error geolocating ' . $physician['full_name'] . 
                ': ' . $gae->getMessage());
        }
    }

    private static function getPhysicianLocationData($physician)
    {
        $geoData = DB::selectOne("select *
                from " . self::$tempLocationTable . "
                where address_1 = :address
                    and City = :city
                    and State_Province = :state
                    and Zip = :zip
            ", [
                'address' => $physician->address_1,
                'city' => $physician->City,
                'state' => $physician->State_Province,
                'zip' => $physician->Zip,
            ]
        );

        if (empty($geoData)) {
            $geoData = self::geolocate($physician);
        }

        return $geoData;
    }

    public static function createPhysicianModel($row)
    {

        $locationData = self::getPhysicianLocationData($row);

        if (!$locationData) { 
            Log::error('Could not geolocate ' . $row->full_name);
            return;
        }

        $physician = \App\Physician::create([
            'aoa_mem_id'                 => trim($row->id),
            'full_name'                  => trim($row->full_name),
            'prefix'                     => trim($row->prefix),
            'first_name'                 => trim($row->first_name),
            'middle_name'                => trim($row->middle_name),
            'last_name'                  => trim($row->last_name),
            'suffix'                     => trim($row->suffix),
            'designation'                => trim($row->designation),
            'SortColumn'                 => trim($row->SortColumn),
            'MemberStatus'               => trim($row->MemberStatus),
            'City'                       => trim($row->City),
            'State_Province'             => trim($row->State_Province),
            'Zip'                        => trim($row->Zip),
            'Country'                    => trim($row->Country),
            'COLLEGE_CODE'               => trim($row->COLLEGE_CODE),
            'YearOfGraduation'           => trim($row->YearOfGraduation),
            'fellows'                    => trim($row->fellows),
            'PrimaryPracticeFocusCode'   => trim($row->PrimaryPracticeFocusCode),
            'PrimaryPracticeFocusArea'   => trim($row->PrimaryPracticeFocusArea),
            'SecondaryPracticeFocusCode' => trim($row->SecondaryPracticeFocusCode),
            'SecondaryPracticeFocusArea' => trim($row->SecondaryPracticeFocusArea),
            'website'                    => trim($row->website),
            'AOABoardCertified'          => ($row->AOABoardCertified == 'YES' ? 1 : 0),
            'address_1'                  => trim($row->address_1),
            'address_2'                  => trim($row->address_2),
            'Phone'                      => trim($row->Phone),
            'Email'                      => trim($row->Email),
            'ABMS'                       => ($row->ABMS == 'YES' ? 1 : 0),
            'Gender'                     => trim($row->Gender),
            'CERT1'                      => trim($row->CERT1),
            'CERT2'                      => trim($row->CERT2),
            'CERT3'                      => trim($row->CERT3),
            'CERT4'                      => trim($row->CERT4),
            'CERT5'                      => trim($row->CERT5),
            'lat'                        => trim($locationData->lat),
            'lon'                        => trim($locationData->lon),
            'geo_confidence'             => trim($locationData->geo_confidence),
            'geo_city'                   => trim($locationData->geo_city),
            'geo_state'                  => trim($locationData->geo_state),
            'geo_matches'                => trim($locationData->geo_matches),
        ]);

        return !empty($physician) ? true : false;
    }
}
