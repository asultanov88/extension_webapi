<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBugStepsToReproducesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bug_steps_to_reproduce', function (Blueprint $table) {
            $table->foreignId('bugId')->references('bugId')->on('module_bugs');
            $table->longtext('stepsToReproduce');
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
        Schema::dropIfExists('bug_steps_to_reproduces');
    }
}
