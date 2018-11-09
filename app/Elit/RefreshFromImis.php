<?php

namespace Elit;

use Illuminate\Database\Schema\Blueprint;
use DB;
use Log;
use Schema;
use Stanley\Geocodio\Client;
use Monolog\Logger;
use Elit\AggregateReporter;
use Elit\Hasher;

/**
 * Update Find Your DO data with data from iMis.
 */
class RefreshFromImis
{
  const TEMP_LOCATION_TABLE = 'temp_locations';

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

  public static function getPhysiciansToBeAdded()
  {
    $q = "
      SELECT full_name
      FROM imis_raw
      WHERE full_name NOT IN (
        SELECT full_name
        FROM physicians
      );
    ";

    return DB::select($q);
  }

  public static function getPhysiciansToBeRemoved()
  {
    $q = "
      SELECT full_name
      FROM physicians
      WHERE full_name NOT IN (
        SELECT full_name
        FROM imis_raw
      );
    ";

    return DB::select($q);
  }

  public static function getConnection()
  {
    $user = env('MSSQL_USERNAME');
    $password = env('MSSQL_PASSWORD');
    $host = env('MSSQL_HOST');
    $db = env('MSSQL_IMIS');

    $db = new \PDO(
      "dblib:host=$host;dbname=$db", 
      $user, 
      $password
    );

    return $db;
  }

  public static function getRows(Logger $log)
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
          and not (id in (037695, 058036))
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
      $log->error($e->getMessage());
      //Log::error($e->getMessage());
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
  
  public static function truncateImisTable(Logger $log)
  {
    DB::table('imis_raw')->truncate();
    //$log->info('Truncated imis_raw');
  }

  public static function dropTempLocationTable()
  {
    return DB::statement("drop table if exists " . self::TEMP_LOCATION_TABLE);
  }

  public static function createTempLocationTable()
  {
    self::dropTempLocationTable();

    $q = "
      CREATE TABLE IF NOT EXISTS " . self::TEMP_LOCATION_TABLE . "(
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
      INSERT INTO " . self::TEMP_LOCATION_TABLE . "
        SELECT City, State_Province, Zip, address_1, address_2, 
          lat, lon, geo_confidence, geo_city, geo_state, geo_matches
        FROM physicians;
    ";

    $result = DB::statement($q);

    return $result;
  }

  public static function truncatePhysiciansTable()
  {
    if (Schema::hasTable('physicians')) {
    DB::table('physicians')->truncate();
    }
  }

  public static function backupPhysiciansTable($backupTableName)
  {

    if (Schema::hasTable($backupTableName)) {
      Schema::drop($backupTableName);
    }

    Schema::create($backupTableName, function (Blueprint $table) {
      $table->timestamps();
      $table->string('id')->unique();
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
      $table->integer('alias_1')->unsigned()->nullable();
      $table->integer('alias_2')->unsigned()->nullable();
      $table->integer('alias_3')->unsigned()->nullable();
      $table->integer('alias_4')->unsigned()->nullable();
      $table->integer('alias_5')->unsigned()->nullable();
      $table->integer('alias_6')->unsigned()->nullable();
    });

    $q = "
      insert into $backupTableName
        select * from physicians
    ";

    return DB::statement($q);
  }

  public static function getImisRawRows()
  {
    return DB::select(DB::raw('select * from imis_raw'));
  }

  public static function parseGeoData($geoDataRaw, $physicianName, $log)
  {
    if (count($geoDataRaw->response->results) == 0) {
      return false;
    } 
  
    try {
      $data = new \StdClass(); 

      $data->lat = $geoDataRaw->response->results[0]->location->lat;
      $data->lon = $geoDataRaw->response->results[0]->location->lng;

      // We don't always get a city back from geocod.io
      if (isset($geoDataRaw->response->results[0]->address_components->city)) {
      $data->geo_city = 
        $geoDataRaw->response->results[0]->address_components->city;
      } else {
      Log::warning('Unable to find city for ' . $physicianName);
      $log->warning('Unable to find city for ' . $physicianName);
      return false;
      }
      $data->geo_state = 
        $geoDataRaw->response->results[0]->address_components->state;
      $data->geo_confidence = $geoDataRaw->response->results[0]->accuracy;
      $data->geo_matches = 0;
      return $data;
    } catch (Exception $e) {
      Log::warning('Unable to parse geodata for ' . $physicianName);
      $log->warning('Unable to parse geodata for ' . $physicianName);
      return false;
    }

  }

  public static function geolocate($physician, $log)
  {
    Log::notice('Fetching geolocation data for ' . $physician->full_name);
    $log->notice('Fetching geolocation data for ' . $physician->full_name);

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
      $geoData = 
        self::parseGeoData($geoDataRaw, $physician->full_name, $log);

      return $geoData;
    } catch (GeocodioAuthError $gae) {
      Log::warning('Error geolocating ' . $physician['full_name'] . 
        ': ' . $gae->getMessage());
      $log->warning('Error geolocating ' . $physician['full_name'] . 
        ': ' . $gae->getMessage());
    } catch (GeocodioDataError $gde) {
      Log::warning('Error geolocating ' . $physician['full_name'] . 
        ': ' . $gae->getMessage());
      $log->warning('Error geolocating ' . $physician['full_name'] . 
        ': ' . $gde->getMessage());
    } catch (GeocodioServerError $gse) {
      Log::warning('Error geolocating ' . $physician['full_name'] . 
        ': ' . $gse->getMessage());
      $log->warning('Error geolocating ' . $physician['full_name'] . 
        ': ' . $gse->getMessage());
    } catch (Exception $e) {
      Log::warning('Error geolocating ' . $physician['full_name'] . 
        ': ' . $e->getMessage());
      $log->warning('Error geolocating ' . $physician['full_name'] . 
        ': ' . $e->getMessage());
    }
  }

  private static function getPhysicianLocationData($physician, Logger $log)
  {
    $geoData = DB::selectOne("select *
        from " . self::TEMP_LOCATION_TABLE . "
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
      $geoData = self::geolocate($physician, $log);
    }

    return $geoData;
  }

  public static function createPhysicianModel($row, Logger $log)
  {

    $locationData = self::getPhysicianLocationData($row, $log);

    if (!$locationData) { 
      Log::error('Could not geolocate ' . $row->full_name);
      return;
    }

    //$aliases = AggregateReporter::getAliases($row->PrimaryPracticeFocusCode);
    $aliases = AggregateReporter::getAliases([
      $row->PrimaryPracticeFocusCode, 
      $row->SecondaryPracticeFocusCode
    ]);

    $id = Hasher::createId($row->id, $row->full_name);

    $physician = \App\Physician::create([
      'id'                 => $id,
      'aoa_mem_id'         => trim($row->id),
      'full_name'          => trim($row->full_name),
      'prefix'           => trim($row->prefix),
      'first_name'         => trim($row->first_name),
      'middle_name'        => trim($row->middle_name),
      'last_name'          => trim($row->last_name),
      'suffix'           => trim($row->suffix),
      'designation'        => trim($row->designation),
      'SortColumn'         => trim($row->SortColumn),
      'MemberStatus'         => trim($row->MemberStatus),
      'City'             => trim($row->City),
      'State_Province'       => trim($row->State_Province),
      'Zip'            => trim($row->Zip),
      'Country'          => trim($row->Country),
      'COLLEGE_CODE'         => trim($row->COLLEGE_CODE),
      'YearOfGraduation'       => trim($row->YearOfGraduation),
      'fellows'          => trim($row->fellows),
      'PrimaryPracticeFocusCode'   => trim($row->PrimaryPracticeFocusCode),
      'PrimaryPracticeFocusArea'   => trim($row->PrimaryPracticeFocusArea),
      'SecondaryPracticeFocusCode' => trim($row->SecondaryPracticeFocusCode),
      'SecondaryPracticeFocusArea' => trim($row->SecondaryPracticeFocusArea),
      'website'          => trim($row->website),
      'AOABoardCertified'      => ($row->AOABoardCertified == 'YES' ? 1 : 0),
      'address_1'          => trim($row->address_1),
      'address_2'          => trim($row->address_2),
      'Phone'            => trim($row->Phone),
      'Email'            => trim($row->Email),
      'ABMS'             => ($row->ABMS == 'YES' ? 1 : 0),
      'Gender'           => trim($row->Gender),
      'CERT1'            => trim($row->CERT1),
      'CERT2'            => trim($row->CERT2),
      'CERT3'            => trim($row->CERT3),
      'CERT4'            => trim($row->CERT4),
      'CERT5'            => trim($row->CERT5),
      'lat'            => trim($locationData->lat),
      'lon'            => trim($locationData->lon),
      'geo_confidence'       => trim($locationData->geo_confidence),
      'geo_city'           => trim($locationData->geo_city),
      'geo_state'          => trim($locationData->geo_state),
      'geo_matches'        => trim($locationData->geo_matches),
      'alias_1'          => empty($aliases[0]) ? null : $aliases[0]->id,
      'alias_2'          => empty($aliases[1]) ? null : $aliases[1]->id,
      'alias_3'          => empty($aliases[2]) ? null : $aliases[2]->id,
      'alias_4'          => empty($aliases[3]) ? null : $aliases[3]->id,
      'alias_5'          => empty($aliases[4]) ? null : $aliases[4]->id,
      'alias_6'          => empty($aliases[5]) ? null : $aliases[5]->id,
    ]);

    return !empty($physician) ? true : false;
  }
}
