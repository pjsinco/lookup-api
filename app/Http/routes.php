<?php

//Route::get('try-this-one', 'LocationController@tryThisOne');

Route::group(['prefix' => 'api/v1'], function() {

    /**
     * Physicians
     *
     */
    Route::get('physician/search', 'PhysicianController@search');
    Route::get('physician/{id}', 'PhysicianController@show');

});
