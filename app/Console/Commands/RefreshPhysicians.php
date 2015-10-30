<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Log;
use Stanley\Geocodio\Client;
use Elit\RefreshFromImis;

class RefreshPhysicians extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'physicians:refresh';

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
        RefreshFromImis::createImisTable();
    }
}
