<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModuleBugsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('module_bugs', function (Blueprint $table) {
            $table->id('bugId')->index();
            $table->foreignId('moduleId')->references('moduleId')->on('Modules');
            $table->integer('lkBugStatusId')->references('lkBugStatusId')->on('lk_bug_statuses');
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
        Schema::dropIfExists('module_bugs');
    }
}
