<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Log;
use Mail;
use Elit\RefreshFromImis;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SwiftMailerHandler;
use Monolog\Handler\BufferHandler;
use Monolog\Formatter\LineFormatter;
use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

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
     * Backup table
     *
     */
    private $backupTableName = 'physicians_safety_backup';

    /**
     * Recipients of the log email
     *
     */
    private $recipients = ['psinco@osteopathic.org',];

    /**
     * Whether the refresh was successful
     *
     */
    private $success = false;

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

        // https://laracasts.com/discuss/channels/requests/log-to-mail
        $swiftMessage = Mail::getSwiftMailer()
          ->createMessage()
          ->setFrom('mail@findyourdo.org', 'Find Your DO Lookup API')
          ->setTo('psinco@osteopathic.org', 'Patrick Sinco')
          ->setBody('hiya');
      
        $mailHandler = new SwiftMailerHandler(
          Mail::getSwiftMailer(), 
          $swiftMessage,
          Logger::ERROR
        );

        $streamHandler = new StreamHandler(
          storage_path('logs/' . $this->logName), 
          Logger::INFO
        );

        $formatter = new LineFormatter(
          "[%datetime%] %level_name%: %message%\n", 
          false,
          true
        );

        $streamHandler->setFormatter($formatter);
        $mailHandler->setFormatter($formatter);

        $this->log->pushHandler($streamHandler);
        $this->log->pushHandler(new BufferHandler ($mailHandler));

    }

    private function refreshImisTable()
    {
        RefreshFromImis::truncateImisTable($this->log);

        $this->info(PHP_EOL.'Fetching rows from iMIS ...'.PHP_EOL);
        $this->log->info('Fetching rows from iMIS ...');

        $rows = RefreshFromImis::getRows($this->log);

        $this->info(sprintf('Retrieved %d rows from iMIS'.PHP_EOL, count($rows)));
        $this->log->info(sprintf('Retrieved %d rows from iMIS', count($rows)));

        $bar = $this->output->createProgressBar(count($rows));
        $this->info('Adding rows to imis_raw ...' . PHP_EOL);
        $this->log->info('Adding rows to imis_raw ...' . PHP_EOL);

        $rowCount = 0;

        foreach ($rows as $row) {
            RefreshFromImis::addRow($row);
            $rowCount++;
            $bar->advance();
        }

        $rows = null; // needed? helps prevent memory leak?

        $bar->finish();

        $msg = sprintf('Added: %d rows to imis_raw', $rowCount);

        $msgForInfo = PHP_EOL . PHP_EOL . $msg . PHP_EOL;

        //Log::info($msg);
        $this->log->info($msg);
        $this->info($msgForInfo);
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
        
        $rows = RefreshFromImis::getImisRawRows();

        if (!$rows) {
          $this->error('Count not create physician models.');
          $this->log->error('Count not create physician models.');
          return;
        }

        RefreshFromImis::truncatePhysiciansTable();

        $this->info('Truncated physicians table.' . PHP_EOL);
        $this->log->info('Truncated physicians table.' . PHP_EOL);

        $this->info('Creating physician models ... ' . PHP_EOL);
        $this->log->info('Creating physician models ... ' . PHP_EOL);

        $bar = $this->output->createProgressBar(count($rows));
      
        foreach ($rows as $row) {
          $created = RefreshFromImis::createPhysicianModel($row, $this->log);
            
          if ($created) {
            $bar->advance();
          } else {
            $this->error('Error creating physician model.');
            $this->log->error('Error creating physician model.');
          }
        }

        $rows = null; // needed? helps prevent memory leak?

        $bar->finish();

        $this->info(PHP_EOL . PHP_EOL . 'Finished successfully!' . PHP_EOL);
        $this->log->info(PHP_EOL . PHP_EOL . 'Finished successfully!' . PHP_EOL);
        $this->success = true;

    }

    private function makeBackup($tableName)
    {
        $backedUp = RefreshFromImis::backupPhysiciansTable($tableName);
        
        if ($backedUp) {
            $this->info('Made safety backup of physicians table: ' . $tableName);
            $this->log->info('Made safety backup of physicians table: ' . $tableName);
        } else {
            $this->error('Could not make safety backup of physicians table.');
            $this->log->error('Could not make safety backup of physicians table.');
            die("Terminated because we could not back up the physicians table.");
        }
    }

    private function createFromBackup($tableName)
    {

        $backupRowCount = DB::table($tableName)
          ->count();

        if ($backupRowCount == 0) {
          die('Terminated process: Backup table is empty.');
        }

        RefreshFromImis::truncatePhysiciansTable();
        
        $q = "
            insert into physicians 
                select * from $tableName;
        ";
        
        return DB::statement($q);
    }

    private function showPhysiciansToBeAdded()
    {
        $newPhysicians = RefreshFromImis::getPhysiciansToBeAdded();
        $info = sprintf('%d new physicians:', count($newPhysicians));
        $this->info(PHP_EOL . $info);
        $this->log->info($info);

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
        $this->log->info($info);

        $removed = array_map(function($item) {
            return $item->full_name;
        }, $removedPhysicians);

        foreach ($removedPhysicians as $name) {
            $this->info("\t" . $name->full_name);
            $this->log->info("\t$name->full_name");
        }

        $this->info(PHP_EOL);
    }

    private function sendMail($subject, $contents) 
    {
      if (empty($subject)) {
        $subject = $this->logName;
      }

      Mail::send([], [], function($message) use ($subject, $contents) {
        $message->to($this->recipients)
          ->from('mail@findyourdo.org', 'Find Your DO Lookup API')
          ->subject($subject)
          ->setBody($contents);
      });

    }

    private function emailLogFile()
    {
      $subject = 'Refresh -- ' . ($this->success ? 'Successful' : 'Error');
      try {
        $contents = \File::get(storage_path('logs/' . $this->logName));
        $this->sendMail($subject, $contents);
      } catch (FileNotFoundException $fnf) {
        $contents = ('Unable to find log file');
        $this->sendMail($subject, $contents);
      }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->log->info('Starting refresh ...');
        $this->info(PHP_EOL . 'Starting refresh ...' . PHP_EOL);

        if ($this->option('frombackup')) {

            $result = $this->createFromBackup($this->backupTableName);
        
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
            $this->makeBackup($this->backupTableName);
            return;
        }
        
        $this->makeBackup($this->backupTableName);
        $this->refreshImisTable();
        $this->showPhysiciansToBeAdded();
        $this->showPhysiciansToBeRemoved();
        $this->createTempTable();
        $this->populateTempTable();
        $this->createPhysicianModels();
        $this->emailLogFile();
    }
}
