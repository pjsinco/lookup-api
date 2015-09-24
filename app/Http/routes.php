<?php

//Route::get('try-this-one', 'LocationController@tryThisOne');

Route::group(['prefix' => 'api/v1'], function() {

    /**
     * Physicians
     *
     */
    Route::get('physicians/search', 'PhysicianController@search');
    Route::get('physicians/{id}', 'PhysicianController@show');

});

Route::get('locations/try-this-one', 'LocationController@tryThisOne');
