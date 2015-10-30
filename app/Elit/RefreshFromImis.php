<?php

namespace Elit;

use DB;
use Log;

/**
 * Update Find Your DO data with data from iMis.
 */
class RefreshFromImis
{
    
    /**
     * 
     */
    public function __construct()
    {
        
    }

    public static function createImisTable()
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

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

var_dump(count($rows)); die();

            foreach ($rows as $row) {
                DB::table('imis_raw')
                    ->insert($row);
            }

//            while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
//                $rowCount++;
//                DB::table('imis_raw')
//                    ->insert($row);
//            }
            
            $msg = sprintf('Refreshing imis_raw table: %d rows', $rowCount);

            Log::info($msg);
            $this->info($msg);

        } catch (\PDOException $e) {
            Log::error($e->getMessage());
            $this->error($e->getMessage());
        }
    }
}
