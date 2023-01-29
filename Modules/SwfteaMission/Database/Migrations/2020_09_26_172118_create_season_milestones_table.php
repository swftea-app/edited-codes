<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSeasonMilestonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('season_milestones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('season_id')->unsigned();
            $table->string('name');
            $table->integer('target');
            $table->decimal('reward')->default(5000);
            $table->text('description');
            $table->timestamps();
        });
        Schema::create('milestone_users', function (Blueprint $table) {
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('milestone_id')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('season_milestones');
    }
}
