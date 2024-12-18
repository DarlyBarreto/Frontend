<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('skins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');  // Relación con usuarios
            $table->unsignedBigInteger('category_id');  // Relación con categoría
            $table->string('name');
            $table->string('image_path');  // Ruta del archivo de la skin
            $table->integer('likes')->default(0);        // Campo para los "me gusta"
            $table->integer('dislikes')->default(0);     // Campo para los "no me gusta"
            $table->timestamps();

            // Relaciones
            $table->foreign('user_id')->references('id')->on('usuarios')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skins');
    }
};
