<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Log;
use Elit\RefreshFromImis;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Carbon\Carbon;


class RefreshPhysicians extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 
        'physicians:refresh 
            {--frombackup} 
            {--imistableonly} 
            {--makesafetybackup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a table of the latest Find Your DO data';

    /**
     * Log for the refresh.
     *
     */
    private $log;
    private $logName;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->logName =
            sprintf(
                'refresh-%s-%s', 
                Carbon::now()->toDateString(),
                Carbon::now()->toTimeString()
            );

        $this->log = new Logger($this->logName);

        $this->log->pushHandler(
            new StreamHandler(storage_path('logs/' . $this->logName)), 
            Logger::INFO
        );

        $this->log->info('Starting refresh ...');
    }

    private function refreshImisTable()
    {
        RefreshFromImis::truncateImisTable($this->log);

        $this->info(PHP_EOL.'Fetching rows from iMIS ...'.PHP_EOL);
        $this->log->info(PHP_EOL.'Fetching rows from iMIS ...'.PHP_EOL);

        $rows = RefreshFromImis::getRows($this->log);

        $this->info(sprintf('Retrieved %d rows from iMIS'.PHP_EOL, count($rows)));
        $this->log->info(sprintf('Retrieved %d rows from iMIS', count($rows)));

        $bar = $this->output->createProgressBar(count($rows));
        $this->info('Adding rows to imis_raw' . PHP_EOL);
        $this->log->info('Adding rows to imis_raw' . PHP_EOL);

        $rowCount = 0;

        foreach ($rows as $row) {
            RefreshFromImis::addRow($row);
            $rowCount++;
            $bar->advance();
        }

        $rows = null; // needed? helps prevent memory leak?

        $bar->finish();

        $msg = sprintf(
            PHP_EOL . PHP_EOL .  'Added: %d rows to imis_raw' . PHP_EOL, 
            $rowCount
        );

        //Log::info($msg);
        $this->log->info($msg);
        $this->info($msg);
    }

    private function createTempTable()
    {
        $created = RefreshFromImis::createTempLocationTable();

        if (!$created) {
            $this->error('Could not create temporary location table.'.PHP_EOL);
            $this->log->error('Could not create temporary location table.'.PHP_EOL);
            die();
        } else {
            $this->info('Created temporary location table'.PHP_EOL); 
            $this->log->info('Created temporary location table'.PHP_EOL); 
        }

    }
    
    private function populateTempTable()
    {
        $populated = RefreshFromImis::populateTempLocationTable();

        if (!$populated) {
            $this->error('Could not populate temporary location table' . PHP_EOL);
            $this->log->error('Could not populate temporary location table' . PHP_EOL);
            die();
        } else {
            $this->info('Successfully populated temporary location table' . PHP_EOL);
            $this->log->info('Successfully populated temporary location table' . PHP_EOL);
        }
    }
    
    private function createPhysicianModels()
    {
        
        $backedUp = RefreshFromImis::backupPhysiciansTable('physicians_backup');
        $this->info('Backed up physicians table.' . PHP_EOL);
        $this->log->info('Backed up physicians table.' . PHP_EOL);

        if ($backedUp) {

            $rows = RefreshFromImis::getImisRawRows();

            RefreshFromImis::truncatePhysiciansTable();
            $this->info('Truncated physicians table.' . PHP_EOL);
            $this->log->info('Truncated physicians table.' . PHP_EOL);

            $this->info('Creating physician models ... ' . PHP_EOL);
            $this->log->info('Creating physician models ... ' . PHP_EOL);

            $bar = $this->output->createProgressBar(count($rows));
            if ($rows) {
                foreach ($rows as $row) {
                    $created = RefreshFromImis::createPhysicianModel($row, $this->log);
                    $bar->advance();
                }
            } else {
                $this->error('Count not create physician models.');
                $this->log->error('Count not create physician models.');
                die();
            }

            $rows = null; // needed? helps prevent memory leak?

            $bar->finish();

            $this->info(PHP_EOL . PHP_EOL . 'Finished!' . PHP_EOL);
            $this->log->info(PHP_EOL . PHP_EOL . 'Finished!' . PHP_EOL);

        } else {
            $this->error('Could not back up physicians table' . PHP_EOL) ;
            $this->log->error('Could not back up physicians table' . PHP_EOL) ;
            die();
        }

    }

    private function createFromBackup()
    {

        RefreshFromImis::truncatePhysiciansTable();
        
        $q = "
            insert into physicians 
                select * from physicians_backup;
        ";
        
        return DB::statement($q);
    }

    private function showPhysiciansToBeAdded()
    {
        $newPhysicians = RefreshFromImis::getPhysiciansToBeAdded();
        $info = sprintf('%d new physicians:', count($newPhysicians));
        $this->info(PHP_EOL . $info);
        $this->log->info($info . PHP_EOL);

        $new = array_map(function($item) {
            return $item->full_name;
        }, $newPhysicians);

        foreach ($newPhysicians as $name) {
            $this->info("\t" . $name->full_name);
            $this->log->info("\t$name->full_name");
        }

        $this->info(PHP_EOL);
    }

    private function showPhysiciansToBeRemoved()
    {
        $removedPhysicians = RefreshFromImis::getPhysiciansToBeRemoved();
        $info = sprintf('%d physicians to be removed:', count($removedPhysicians));
        $this->info(PHP_EOL . $info);
        $this->log->info($info . PHP_EOL);

        $removed = array_map(function($item) {
            return $item->full_name;
        }, $removedPhysicians);

        foreach ($removedPhysicians as $name) {
            $this->info("\t" . $name->full_name);
            $this->log->info("\t$name->full_name");
        }

        $this->info(PHP_EOL);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->option('frombackup')) {

            $result = $this->createFromBackup();
        
            if ($result) {
                $this->info(
                    PHP_EOL . 
                    'Successfully populated physicians table from backup.' . 
                    PHP_EOL
                );
            } else {
                $this->error(
                    PHP_EOL . 
                    'Could not populate physicians table from backup.' . 
                    PHP_EOL
                );

            }
            return;
        } else if ($this->option('imistableonly')) {
            $this->refreshImisTable();
            return;
        } else if ($this->option('makesafetybackup')) {
            $tableName = 'physicians_safety_backup';
            $backedUp = RefreshFromImis::backupPhysiciansTable($tableName);
            
            if ($backedUp) {
                $this->info('Made safety backup of physicians table: ' . $tableName);
            } else {
                $this->error('Could not make safety backup of physicians table.');
            }
            
            return;
        }
        
        
        $this->refreshImisTable();
        $this->showPhysiciansToBeAdded();
        $this->showPhysiciansToBeRemoved();
        $this->createTempTable();
        $this->populateTempTable();
        $this->createPhysicianModels();
    }
}
