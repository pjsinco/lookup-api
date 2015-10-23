<?php

Route::get('/', function() {

    echo '...';

});

Route::group(['prefix' => 'api/v1'], function() {

    /**
     * Physicians
     *
     */
    Route::get('physicians/names/search', 'PhysicianController@nameSearch');
    Route::get('physicians/search', 'PhysicianController@search');
    Route::get('physicians/{id}', 'PhysicianController@show');

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

Route::get('refresh', 'RefreshController@refresh');

Route::get('test/mssql/{id}', function($id) {
    $user = env('MSSQL_USERNAME');
    $password = env('MSSQL_PASSWORD');

    $db = new PDO(
        'dblib:host=sql05-1.aoanet.local;dbname=imis', 
        $user, 
        $password
    );

    $q = "select * from imis.dbo.vfindyourdo where id = $id";
    //$stmt = $db->query($q);
    $stmt = $db->prepare($q);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
     
    $current = App\Physician::where('aoa_mem_id', '=', (int) $row['id'])->first();
});


