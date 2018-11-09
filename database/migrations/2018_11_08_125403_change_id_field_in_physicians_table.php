<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIdFieldInPhysiciansTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('physicians', function (Blueprint $table) {
      $table->string('id')->change();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('physicians', function (Blueprint $table) {
      $table->increments('id')->change();
    });
  }
}
