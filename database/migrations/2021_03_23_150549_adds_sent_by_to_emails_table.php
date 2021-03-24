<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddsSentByToEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('emails', function (Blueprint $table) {
            $column = $table->unsignedBigInteger('sent_by');

            if (config('database.default') == 'sqlite') {
                $column->nullable();//this is a hack, cos of how sqlite treats added columns
            }

            $table->foreign('sent_by')->references('id')
                ->on('users')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropForeign('emails_sent_by_foreign');
            $table->dropColumn(['sent_by']);
        });
    }
}
