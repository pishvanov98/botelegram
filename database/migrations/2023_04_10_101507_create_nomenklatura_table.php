<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNomenklaturaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('nomenklatura', function (Blueprint $table) {
            $table->increments('id');
            $table->string("kod_nomenklatura");
            $table->string("name_nomenklatura");
            $table->string('harakteristic_nomenklatura');
            $table->string('storage_nomenklatura');
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
        Schema::dropIfExists('nomenklatura');
    }
}
