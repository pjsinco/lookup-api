<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use DB;
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

    public function refresh()
    {
        //$stmt = $this->getPdoStatementForImisRows();

        $this->refreshImisRawTable();

        $this->createTempLocationTable();

        $this->createNewPhysicianModels();

        $this->dropTempLocationTable();

        //while (($physician = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
        //    $aoa_mem_id = (int) $physician['id'];
        //    $current = $this->getCurrentPhysician($aoa_mem_id);
        //
        //    if (!$current) {
        //        $this->addPhysician($physician);
        //    }
        //}
    }

    private function geolocate($physician)
    {
        $client = new Client('aca4585cbbdf2f8589c58bb4606c56fd45db3bb');
        $data = sprintf(
            '%s, %s, %s %s',
            $physician['address_1'],
            $physician['City'],
            $physician['State'],
            $physician['Zip']
        );

        $geoData = $client->get($data);

dd($geoData);

        return $geoData;
    }

    private function dropTempLocationTable()
    {
        return DB::statement('drop table ?', [$this->tempLocationTable]);
    }

    private function getPhysicianLocationData($physician)
    {
        $data = DB::select("
            select * 
            from ? 
            where address_1 = ?
                and address_2 = ?
                and City = ?
                and State_Province = ?
                and Zip = ?
            ", [
                $this->tempLocationTable, 
                $row[23],
                $row[24],
                $row[10],
                $row[11],
                $row[12],
            ]
        );

        if (empty($data)) {
            $data = $this->geolocate($physician);
        }

        return $data;
    }

    private function createNewPhysicianModels()
    {
        DB::table('physicians')->truncate();

        $rows = DB::select('select * from imis_raw');

        foreach ($rows as $lineIndex => $row) {
            
            $locationData = $this->getPhysicianLocationData($row);


            $physician = App\Physician::create([
                'aoa_mem_id'                 => $row[0],
                'full_name'                  => $row[1],
                'prefix'                     => $row[2],
                'first_name'                 => $row[3],
                'middle_name'                => $row[4],
                'last_name'                  => $row[5],
                'suffix'                     => $row[6],
                'designation'                => $row[7],
                'SortColumn'                 => $row[8],
                'MemberStatus'               => $row[9],
                'City'                       => $row[10],
                'State_Province'             => $row[11],
                'Zip'                        => $row[12],
                'Country'                    => $row[13],
                'COLLEGE_CODE'               => $row[14],
                'YearOfGraduation'           => $row[15],
                'fellows'                    => $row[16],
                'PrimaryPracticeFocusCode'   => $row[17],
                'PrimaryPracticeFocusArea'   => $row[18],
                'SecondaryPracticeFocusCode' => $row[19],
                'SecondaryPracticeFocusArea' => $row[20],
                'website'                    => $row[21],
                'AOABoardCertified'          => ($row[22] == 'YES' ? 1 : 0),
                'address_1'                  => $row[23],
                'address_2'                  => $row[24],
                'Phone'                      => $row[25],
                'Email'                      => $row[26],
                'ABMS'                       => ($row[27] == 'YES' ? 1 : 0),
                'Gender'                     => $row[28],
                'CERT1'                      => $row[29],
                'CERT2'                      => $row[30],
                'CERT3'                      => $row[31],
                'CERT4'                      => $row[32],
                'CERT5'                      => $row[33],
                'lat'                        => $locationData['lat'],
                'lon'                        => $locationData['lon'],
                'geo_confidence'             => $locationData['geo_confidence'],
                'geo_city'                   => $locationData['geo_city'],
                'geo_state'                  => $locationData['geo_state'],
                'geo_matches'                => ($locationdata['matches'] == 'True' ? 1 : 0),
            ]);
        }
        
    }

    private function createTempLocationTable()
    {

        DB::statement("
            CREATE TABLE IF NOT EXISTS $this->tempLocationTable (
                City VARCHAR(255),
                State_Province VARCHAR(16),
                Zip VARCHAR(16),
                address_1 VARCHAR(255),
                address_2 VARCHAR(255) 
            );
        ");

        DB::statement("
            INSERT INTO $this->tempLocationTable
                SELECT City, State_Province, Zip, address_1, address_2
                FROM physicians;
        ");
    }

    private function refreshImisRawTable()
    {
        DB::table('imis_raw')->truncate();

        $user = env('MSSQL_USERNAME');
        $password = env('MSSQL_PASSWORD');

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
        $stmt->execute();

        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
            DB::table('imis_raw')
                ->insert($row);
            //DB::insert('insert into imis_raw values(');
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
