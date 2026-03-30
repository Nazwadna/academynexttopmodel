<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePopularModelsTable extends Migration
{
    public function up()
    {
        Schema::create('popular_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_model');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('popular_models');
    }
}
