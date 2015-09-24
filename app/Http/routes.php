<?php

Route::get('/', function() {

    echo '...';

});

Route::group(['prefix' => 'api/v1'], function() {

    /**
     * Physicians
     *
     */
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
