<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGiftColor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Schema::hasTable('all_gifts')) {
          Schema::table('all_gifts', function (Blueprint $table) {
            $table->string('color')->default('#E6397F');
          });
        } else {
          Schema::create('all_gifts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->double('price',8,2);
            $table->double('discount',8,2)->default(0.00);
            $table->string('gift_image');
            $table->string('color')->default('#E6397F');
            $table->timestamps();
          });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {

        });
    }
}
