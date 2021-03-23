<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('from');
            $table->string('to');
            $table->string('subject');
            $table->text('text_content')->nullable();
            $table->longText('html_content')->nullable();
            $table->longText('attachments')->nullable();
            $table->string('status')->default('POSTED');
            //No desire to store attachments for now, or could store the filenames
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
        Schema::dropIfExists('emails');
    }
}
