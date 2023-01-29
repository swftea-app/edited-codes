<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMissionTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mission_tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('type');
            $table->integer('amount');
            $table->string('identifier');
            $table->text('abstract');
            $table->string('banner');
            $table->decimal('reward');
            $table->timestamps();
        });
        Schema::create('week_tasks', function (Blueprint $table) {
          $table->bigInteger('week_id')->unsigned();
          $table->bigInteger('task_id')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mission_tasks');
    }
}
