<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use DB;
use Log;
use Stanley\Geocodio\Client;

class RefreshController extends Controller
{
    /**
     * Fields in the iMIS FindYourDO table
     *
     */
    private $iMisHeaders = [
        'id',
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
        'CERT5'
    ];

    private $tempLocationTable = 'temp_locations';
    private $countGeoLocated = 0;
    private $countImisRaw = 0;

    public function refresh()
    {
        ini_set('max_execution_time', 300);

        Log::info('Visited refresh page'); // TODO make info more useful

        $this->refreshImisRawTable();

        $createdTempTable = $this->createTempLocationTable();

        $populatedTempTable = $this->populateTempLocationTable(); 
            
        $this->createPhysicianModels();

        $query = $this->dropTempLocationTable();
    }

    private function parseGeoData($geoDataRaw)
    {
dd($geoDataRaw);
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

    private function geolocate($physician)
    {
        Log::notice('Fetching geolocation data for ' . $physician->full_name);

        $client = new Client('aca4585cbbdf2f8589c58bb4606c56fd45db3bb');
        $data = sprintf(
            '%s, %s, %s %s',
            $physician->address_1,
            $physician->City,
            $physician->State_Province,
            $physician->Zip
        );

        try {
            $geoDataRaw = $client->get($data);
            $geoData = $this->parseGeoData($geoDataRaw);

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

    private function dropTempLocationTable()
    {
        return DB::statement("drop table if exists $this->tempLocationTable");
    }

    private function getPhysicianLocationData($physician)
    {

        $geoData = DB::selectOne("select *
                from $this->tempLocationTable
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
//        $q = "
//            select * 
//            from $this->tempLocationTable
//            where address_1 = \"$physician->address_1\"
//                and City = \"$physician->City\" 
//                and State_Province = \"$physician->State_Province\"
//                and Zip = \"$physician->Zip\"
//        ";
//
//        $geoData = DB::selectOne($q);

        if (empty($geoData)) {
            $geoData = $this->geolocate($physician);
        }

        return $geoData;
    }

    private function createPhysicianModels()
    {
        DB::table('physicians')->truncate();

        $rows = DB::select(DB::raw('select * from imis_raw'));

        foreach ($rows as $row) {

            $locationData = $this->getPhysicianLocationData($row);

            $physician = \App\Physician::create([
                'aoa_mem_id'                 => $row->id,
                'full_name'                  => $row->full_name,
                'prefix'                     => $row->prefix,
                'first_name'                 => $row->first_name,
                'middle_name'                => $row->middle_name,
                'last_name'                  => $row->last_name,
                'suffix'                     => $row->suffix,
                'designation'                => $row->designation,
                'SortColumn'                 => $row->SortColumn,
                'MemberStatus'               => $row->MemberStatus,
                'City'                       => $row->City,
                'State_Province'             => $row->State_Province,
                'Zip'                        => $row->Zip,
                'Country'                    => $row->Country,
                'COLLEGE_CODE'               => $row->COLLEGE_CODE,
                'YearOfGraduation'           => $row->YearOfGraduation,
                'fellows'                    => $row->fellows,
                'PrimaryPracticeFocusCode'   => $row->PrimaryPracticeFocusCode,
                'PrimaryPracticeFocusArea'   => $row->PrimaryPracticeFocusArea,
                'SecondaryPracticeFocusCode' => $row->SecondaryPracticeFocusCode,
                'SecondaryPracticeFocusArea' => $row->SecondaryPracticeFocusArea,
                'website'                    => $row->website,
                'AOABoardCertified'          => ($row->AOABoardCertified == 'YES' ? 1 : 0),
                'address_1'                  => $row->address_1,
                'address_2'                  => $row->address_2,
                'Phone'                      => $row->Phone,
                'Email'                      => $row->Email,
                'ABMS'                       => ($row->ABMS == 'YES' ? 1 : 0),
                'Gender'                     => $row->Gender,
                'CERT1'                      => $row->CERT1,
                'CERT2'                      => $row->CERT2,
                'CERT3'                      => $row->CERT3,
                'CERT4'                      => $row->CERT4,
                'CERT5'                      => $row->CERT5,
                'lat'                        => $locationData->lat,
                'lon'                        => $locationData->lon,
                'geo_confidence'             => $locationData->geo_confidence,
                'geo_city'                   => $locationData->geo_city,
                'geo_state'                  => $locationData->geo_state,
                'geo_matches'                => $locationData->geo_matches,
            ]);
        }
        
    }

    private function populateTempLocationTable()
    {
        $q = "
            INSERT INTO $this->tempLocationTable
                SELECT City, State_Province, Zip, address_1, address_2, 
                    lat, lon, geo_confidence, geo_city, geo_state, geo_matches
                FROM physicians;
        ";

        $result = DB::statement($q);

        if (!$result) {
            Log::error('Could not populate ' . $this->tempLocationTable);
        } else {
            Log::info('Successfully populated ' . $this->tempLocationTable);
        }

        return $result;
    }

    private function createTempLocationTable()
    {
        
        $this->dropTempLocationTable();

        $q = "
            CREATE TABLE IF NOT EXISTS $this->tempLocationTable (
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

        if (!$result) {
            Log::error('Could not create ' . $this->tempLocationTable);
        } else {
            Log::info('Successfully created ' . $this->tempLocationTable);
        }

        return $result;
        
    }

    private function refreshImisRawTable()
    {
        DB::table('imis_raw')->truncate();

        $user = env('MSSQL_USERNAME');
        $password = env('MSSQL_PASSWORD');

        try {
            $db = new \PDO(
                'dblib:host=sql05-1.aoanet.local;dbname=imis', 
                $user, 
                $password
            );

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
    
            $rowCount = 0;

            while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
                $rowCount++;
                DB::table('imis_raw')
                    ->insert($row);
            }
            
            Log::info(sprintf(
                'Refreshing imis_raw table: %d rows',
                $rowCount
            ));

        } catch (\PDOException $e) {
            Log::error($e->getMessage());
        }

    }

    private function checkImisTableHeaders()
    {

    }
    
//    private function addPhysician($physician)
//    {
//        echo 'We\'re going to add this physician: ';
//        echo '<pre>'; echo $physician['full_name']; echo '</pre>'; ;
//    }

//    private function getCurrentPhysician($id)
//    {
//        return \App\Physician::where('aoa_mem_id', '=', $id)->first();
//    }

//    private function getPdoStatementForImisRows()
//    {
//        $user = env('MSSQL_USERNAME');
//        $password = env('MSSQL_PASSWORD');
//
//        $db = new \PDO(
//            'dblib:host=sql05-1.aoanet.local;dbname=imis', 
//            $user, 
//            $password
//        );
//
//        $q = "
//            SELECT * 
//            FROM imis.dbo.vFindYourDO 
//            WHERE country = 'USA'
//                and not (first_name like '%test%' or 
//                    first_name like '%AOA%'
//                )
//            ORDER BY id
//        ";
//        //$stmt = $db->query($q);
//        $stmt = $db->prepare($q);
//        $stmt->execute();
//
//        return $stmt;
//    }


}
