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
        Schema::create('garanties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('moto_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contrat_leasing_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('generale'); // moteur | pieces | generale
            $table->date('date_debut');
            $table->date('date_fin');
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contrat_leasing_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('contrat'); // contrat | facture | garantie
            $table->string('libelle');
            $table->string('fichier_url')->nullable();
            $table->date('date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('garanties');
    }
};
