<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Obvestila extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('obvestila', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('type'); // obvestilo je lahko globalno (global), razredno (class)
            $table->boolean('is_event')->default(false); // je to dogodek
            $table->string('school')->nullable(); // ime šole (za globalna obvestila)
            $table->string('class')->nullable(); // ime oddelka (za razredna obvestila)
            $table->date('datum_prikaza')->nullable(); // od kdaj je obvestilo prikazano
            $table->date('datum_obvestila')->nullable(); // kdaj je obvestilo aktualno (npr. sistematski pregled kdaj je datum)
            $table->date('datum_umika')->nullable(); // kdaj obvestilo ni več vidno (npr.  po datumu dogodka)
            $table->string('title');
            $table->string('content');
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
