<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('games', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->enum('mode', ['FREE_THROW', 'DRILLS']);
            $table->float('release_angle', 6, 3);
            $table->float('release_time', 6, 3);
            $table->float('elbow_angle', 6, 3);
            $table->float('leg_angle', 6, 3);
            $table->smallInteger('score');
            $table->smallInteger('try_count');
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
        Schema::dropIfExists('games');
    }
}
