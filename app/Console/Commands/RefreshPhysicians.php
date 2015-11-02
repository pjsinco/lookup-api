<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Log;
use Elit\RefreshFromImis;

class RefreshPhysicians extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'physicians:refresh {--frombackup} {--imistableonly}';

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

    private function refreshImisTable()
    {
        RefreshFromImis::truncateImisTable();

        $this->info(PHP_EOL.'Fetching rows from iMIS ...'.PHP_EOL);

        $rows = RefreshFromImis::getRows();

        $this->info(sprintf('Retrieved %d rows'.PHP_EOL, count($rows)));

        $bar = $this->output->createProgressBar(count($rows));
        $this->info('Adding rows to imis_raw' . PHP_EOL);

        $rowCount = 0;

        foreach ($rows as $row) {
            RefreshFromImis::addRow($row);
            $rowCount++;
            $bar->advance();
        }

        $rows = null; // needed? helps prevent memory leak?

        $bar->finish();

        $msg = sprintf(
            PHP_EOL . PHP_EOL .  'Added: %d rows' . PHP_EOL, 
            $rowCount
        );

        Log::info($msg);
        $this->info($msg);
    }

    private function createTempTable()
    {
        $created = RefreshFromImis::createTempLocationTable();

        if (!$created) {
            $this->error('Could not create temporary location table.'.PHP_EOL);
            die();
        } else {
            $this->info('Created temporary location table'.PHP_EOL); 
        }

    }
    
    private function populateTempTable()
    {
        $populated = RefreshFromImis::populateTempLocationTable();

        if (!$populated) {
            $this->error('Could not populate temporary location table' . PHP_EOL);
            die();
        } else {
            $this->info('Successfully populated temporary location table' . PHP_EOL);
        }
    }
    
    private function createPhysicianModels()
    {
        
        $backedUp = RefreshFromImis::backupPhysiciansTable();
        $this->info('Backed up physicians table.' . PHP_EOL);

        if ($backedUp) {

            $rows = RefreshFromImis::getImisRawRows();

            RefreshFromImis::truncatePhysiciansTable();
            $this->info('Truncated physicians table.' . PHP_EOL);

            $this->info('Creating physician models ... ' . PHP_EOL);

            $bar = $this->output->createProgressBar(count($rows));
            if ($rows) {
                foreach ($rows as $row) {
                    $created = RefreshFromImis::createPhysicianModel($row);
                    $bar->advance();
                }
            } else {
                $this->error('Count not create physician models.');
                die();
            }

            $rows = null; // needed? helps prevent memory leak?

            $bar->finish();

            $this->info(PHP_EOL . PHP_EOL . 'Finished!' . PHP_EOL);

        } else {
            $this->error('Could not back up physicians table' . PHP_EOL) ;
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
        $this->info(
            PHP_EOL . 
            sprintf('%d new physicians:', count($newPhysicians))
        );

        foreach ($newPhysicians as $name) {
            $this->info("\t" . $name->full_name);
        }

        $this->info(PHP_EOL);
    }

    private function showPhysiciansToBeRemoved()
    {
        $removedPhysicians = RefreshFromImis::getPhysiciansToBeRemoved();
        $this->info(
            PHP_EOL .  
            sprintf('%d physicians to be removed:', count($removedPhysicians))
        );

        foreach ($removedPhysicians as $name) {
            $this->info("\t" . $name->full_name);
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
        }
        
        $this->refreshImisTable();
        $this->showPhysiciansToBeAdded();
        $this->showPhysiciansToBeRemoved();
        $this->createTempTable();
        $this->populateTempTable();
        $this->createPhysicianModels();
    }
}
