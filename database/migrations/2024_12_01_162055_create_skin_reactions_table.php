<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('skin_reactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('skin_id');
            $table->enum('type', ['like', 'dislike']);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('usuarios')->onDelete('cascade');
            $table->foreign('skin_id')->references('id')->on('skins')->onDelete('cascade');

            // Un usuario solo puede tener una reacción por skin
            $table->unique(['user_id', 'skin_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('skin_reactions');
    }
};
