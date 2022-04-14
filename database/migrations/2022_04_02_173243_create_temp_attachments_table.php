<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTempAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('temp_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->integer('clientId');
            $table->string('tempPath');
            $table->string('fileName');
            $table->integer('isPermanent')->default('0');
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
        Schema::dropIfExists('temp_attachments');
    }
}
