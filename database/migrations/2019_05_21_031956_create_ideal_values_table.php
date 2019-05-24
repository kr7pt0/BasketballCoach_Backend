<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIdealValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ideal_values', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('key', ['RELEASE_ANGLE', 'RELEASE_TIME', 'ELBOW_ANGLE', 'LEG_ANGLE']);
            $table->float('val', 4, 1);
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
        Schema::dropIfExists('ideal_values');
    }
}
