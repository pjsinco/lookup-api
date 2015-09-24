<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Physician extends Model
{
    //public $timestamps = false;
    //protected $primaryKey = 'aoa_mem_id';

    protected $fillable = [
        'prefix',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'designation',
        'City',
        'State_Province',
        'Zip',
        'Country',
        'PrimaryPracticeFocusCode',
        'PrimaryPracticeFocusArea',
        'SecondaryPracticeFocusCode',
        'SecondaryPracticeFocusArea',
        'website',
        'address_1',
        'address_2',
        'Phone',
        'Email',
    ];

    public $hidden = [
        'id',
        'aoa_mem_id',
    ];
}



