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
        Schema::create('paniers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->unique()->constrained()->cascadeOnDelete(); // un panier par client
            $table->timestamps();
        });

        Schema::create('panier_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('piece_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantite')->default(1);
            $table->timestamps();
            $table->unique(['panier_id', 'piece_id']); // une seule ligne par pièce
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('panier_lignes');
        Schema::dropIfExists('paniers');
    }
};
