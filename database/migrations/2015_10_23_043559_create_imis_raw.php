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
            $table->string('Zip');
            $table->string('Country');
            $table->string('COLLEGE_CODE');
            $table->string('YearOfGraduation', 16);
            $table->string('fellows')->nullable();
            $table->string('PrimaryPracticeFocusCode', 16);
            $table->string('PrimaryPracticeFocusArea');
            $table->string('SecondaryPracticeFocusCode', 16)->nullable();
            $table->string('SecondaryPracticeFocusArea')->nullable();
            $table->string('website')->nullable();
            $table->string('AOABoardCertified')->nullable();
            $table->string('address_1');
            $table->string('address_2')->nullable();
            $table->string('Phone', 16)->nullable();
            $table->string('Email')->nullable();
            $table->string('ABMS');
            $table->string('Gender', 1);
            $table->string('CERT1')->nullable();
            $table->string('CERT2')->nullable();
            $table->string('CERT3')->nullable();
            $table->string('CERT4')->nullable();
            $table->string('CERT5')->nullable();
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
