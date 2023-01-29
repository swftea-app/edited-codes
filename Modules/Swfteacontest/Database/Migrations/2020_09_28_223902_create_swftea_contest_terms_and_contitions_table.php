<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSwfteaContestTermsAndContitionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('swftea_contest_terms_and_contitions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('swfteacontest_id')->unsigned();
            $table->text('text');
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
        Schema::dropIfExists('swftea_contest_terms_and_contitions');
    }
}
