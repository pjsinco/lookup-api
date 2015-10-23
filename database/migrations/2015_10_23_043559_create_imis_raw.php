<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImisRaw extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('imis_raw', function (Blueprint $table) {
            $table->string('id');
            $table->string('full_name');
            $table->string('prefix', 24);
            $table->string('first_name');
            $table->string('middle_name');
            $table->string('last_name');
            $table->string('suffix', 24);
            $table->string('designation', 24);
            $table->string('SortColumn');
            $table->string('MemberStatus', 48);
            $table->string('City');
            $table->string('State_Province', 16);
            $table->string('Zip');
            $table->string('Country');
            $table->string('COLLEGE_CODE');
            $table->string('YearOfGraduation', 16);
            $table->string('fellows');
            $table->string('PrimaryPracticeFocusCode', 16);
            $table->string('PrimaryPracticeFocusArea');
            $table->string('SecondaryPracticeFocusCode', 16);
            $table->string('SecondaryPracticeFocusArea');
            $table->string('website');
            $table->string('AOABoardCertified');
            $table->string('address_1');
            $table->string('address_2');
            $table->string('Phone', 16);
            $table->string('Email');
            $table->string('ABMS');
            $table->string('Gender', 1);
            $table->string('CERT1');
            $table->string('CERT2');
            $table->string('CERT3');
            $table->string('CERT4');
            $table->string('CERT5');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('imis_raw');
    }
}
