<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhysiciansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('physicians', function (Blueprint $table) {
            $table->timestamps();
            $table->increments('id');
            $table->string('aoa_mem_id');
            $table->string('full_name');
            $table->string('prefix');
            $table->string('first_name');
            $table->string('middle_name');
            $table->string('last_name');
            $table->string('suffix');
            $table->string('designation');
            $table->string('SortColumn');
            $table->string('MemberStatus');
            $table->string('City');
            $table->string('State_Province');
            $table->string('Zip');
            $table->string('Country');
            $table->string('COLLEGE_CODE');
            $table->string('YearOfGraduation');
            $table->string('fellows');
            $table->string('PrimaryPracticeFocusCode');
            $table->string('PrimaryPracticeFocusArea');
            $table->string('SecondaryPracticeFocusCode');
            $table->string('SecondaryPracticeFocusArea');
            $table->string('website');
            $table->boolean('AOABoardCertified');
            $table->string('address_1');
            $table->string('address_2');
            $table->string('Phone');
            $table->string('Email');
            $table->boolean('ABMS');
            $table->float('lat', 10, 6);
            $table->float('lon', 10, 6);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('physicians');
    }
}
