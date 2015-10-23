<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ImisRaw extends Model
{
    protected $timestamps = false;
    protected $incrementing = false;
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'full_name',
        'prefix',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'designation',
        'SortColumn',
        'MemberStatus',
        'City',
        'State_Province',
        'Zip',
        'Country',
        'COLLEGE_CODE',
        'YearOfGraduation',
        'fellows',
        'PrimaryPracticeFocusCode',
        'PrimaryPracticeFocusArea',
        'SecondaryPracticeFocusCode',
        'SecondaryPracticeFocusArea',
        'website',
        'AOABoardCertified',
        'address_1',
        'address_2',
        'Phone',
        'Email',
        'ABMS',
        'Gender',
        'CERT1',
        'CERT2',
        'CERT3',
        'CERT4',
        'CERT5'
    ];
}
