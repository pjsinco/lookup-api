<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Log;
use Stanley\Geocodio\Client;

class CreateImisTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'imis:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a table of the latest Find Your DO data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
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
            
            $msg = sprintf('Refreshing imis_raw table: %d rows', $rowCount);

            Log::info($msg);
            $this->info($msg);

        } catch (\PDOException $e) {
            Log::error($e->getMessage());
            $this->error($e->getMessage());
        }
    }
}
