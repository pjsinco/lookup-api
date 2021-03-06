<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public static function random()
    {
        $location = DB::table('locations')
            ->orderByRaw('RAND()')
            ->first();
        return $location;
    }


}

