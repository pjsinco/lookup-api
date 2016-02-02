<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSpecialtyAliasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('specialty_alias', function (Blueprint $table) {
            $table->string('specialty_id');
            $table->integer('alias_id')->unsigned();

            $table->foreign('specialty_id')
                ->references('code')
                ->on('specialties')
                ->onDelete('cascade');

            $table->foreign('alias_id')
                ->references('id')
                ->on('aliases')
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
        Schema::drop('specialty_alias');
    }
}
