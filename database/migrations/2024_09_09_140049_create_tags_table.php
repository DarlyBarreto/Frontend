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
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

// Tabla pivot entre skins y tags
        Schema::create('skin_tag', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('skin_id');
            $table->unsignedBigInteger('tag_id');

            // Relaciones
            $table->foreign('skin_id')->references('id')->on('skins')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
