<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('game_id');
            $table->smallInteger('x');
            $table->smallInteger('y');
            $table->boolean('success');
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
        Schema::dropIfExists('shots');
    }
}
