<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ObvestilaEvents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('obvestila_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_obvestila')->references('id')->on('obvestila')->onDelete('cascade');
            $table->string('title');
            $table->string('content');
            $table->dateTime('zacetek_dogodka');
            $table->dateTime('konec_dogodka');
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
        //
    }
}
