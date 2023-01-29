<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMissionSeasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mission_seasons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string("name");
            $table->decimal("points");
            $table->string("banner");
            $table->string("background");
            $table->dateTimeTz('start_at');
            $table->dateTimeTz('end_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mission_seasons');
    }
}
