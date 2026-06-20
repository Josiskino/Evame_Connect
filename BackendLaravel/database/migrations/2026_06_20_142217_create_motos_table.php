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
        Schema::create('motos', function (Blueprint $table) {
            $table->id();
            $table->string('modele');
            $table->string('couleur')->nullable();
            $table->string('cylindree')->nullable();
            $table->unsignedBigInteger('prix'); // en FCFA (entier)
            $table->string('image_url')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('seuil_alerte')->default(3); // seuil d'alerte stock faible
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('motos');
    }
};
