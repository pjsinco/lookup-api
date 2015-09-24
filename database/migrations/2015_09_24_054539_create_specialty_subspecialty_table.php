<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSpecialtySubspecialtyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('specialty_subspecialty', function (Blueprint $table) {
            $table->string('specialty_id');
            $table->string('subspecialty_id');

            $table->foreign('specialty_id')
                ->references('code')
                ->on('specialties')
                ->onDelete('cascade');

            $table->foreign('subspecialty_id')
                ->references('code')
                ->on('specialties')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('specialty_subspecialty');
    }
}

