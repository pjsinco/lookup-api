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
            $table->string('aoa_mem_id', 16);
            $table->string('full_name');
            $table->string('prefix', 24)->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix', 24)->nullable();
            $table->string('designation', 24);
            $table->string('SortColumn');
            $table->string('MemberStatus', 48);
            $table->string('City');
            $table->string('State_Province', 16);
            $table->string('Zip', 16);
            $table->string('Country');
            $table->string('COLLEGE_CODE');
            $table->string('YearOfGraduation', 16);
            $table->string('fellows')->nullable();
            $table->string('PrimaryPracticeFocusCode', 16);
            $table->string('PrimaryPracticeFocusArea');
            $table->string('SecondaryPracticeFocusCode', 16)->nullable();
            $table->string('SecondaryPracticeFocusArea')->nullable();
            $table->string('website')->nullable();
            $table->boolean('AOABoardCertified');
            $table->string('address_1');
            $table->string('address_2')->nullable();
            $table->string('Phone', 16)->nullable();
            $table->string('Email')->nullable();
            $table->boolean('ABMS');
            $table->char('Gender', 1);
            $table->string('CERT1')->nullable();
            $table->string('CERT2')->nullable();
            $table->string('CERT3')->nullable();
            $table->string('CERT4')->nullable();
            $table->string('CERT5')->nullable();
            $table->float('lat', 10, 6);
            $table->float('lon', 10, 6);
            $table->string('geo_confidence');
            $table->string('geo_city');
            $table->string('geo_state');
            $table->boolean('geo_matches');
            $table->integer('alias_1')->unsigned()->nullable();
            $table->integer('alias_2')->unsigned()->nullable();
            $table->integer('alias_3')->unsigned()->nullable();
            $table->integer('alias_4')->unsigned()->nullable();
            $table->integer('alias_5')->unsigned()->nullable();
            $table->integer('alias_6')->unsigned()->nullable();
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


