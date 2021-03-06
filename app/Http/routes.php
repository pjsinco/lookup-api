<?php

Route::get('/', function() {
  echo '<html><head><link rel="stylesheet" type="text/css" /></head></html>';
});

Route::group(['prefix' => 'api/v1'], function() {
  /**
   * Doctors
   *
   */
  Route::get('doctors/search', 'DoctorController@search');
  Route::get('doctors/{id}', 'DoctorController@show');

  /**
   * Specialties
   *
   */
  Route::get('specialties', 'SpecialtyController@index');

  /**
   * Locations
   *
   */
  Route::get('locations', 'LocationController@index');
  Route::get('locations/search', 'LocationController@search');
  Route::get('locations/{location}', 'LocationController@show');
});

Route::get('locations/try-this-one', 'LocationController@tryThisOne');

Route::get('test/mssql/{id}', function($id) {
  $user = env('MSSQL_USERNAME');
  $password = env('MSSQL_PASSWORD');
  $host = env('MSSQL_HOST');
  $db = env('MSSQL_IMIS');

  $db = new PDO(
    "dblib:host=$host;dbname=$db", 
    $user, 
    $password
  );

  $q = "select * from imis.dbo.vfindyourdo where id = $id";
  $stmt = $db->prepare($q);
  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
   
  dd($row);
});
