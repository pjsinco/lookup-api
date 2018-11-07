<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Specialty extends Model
{
    protected $guarded = [];
    public $timestamps = null;
    protected $primaryKey = 'code';
    public $incrementing = false;
}

