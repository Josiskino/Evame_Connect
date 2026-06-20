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
        Schema::create('contrat_leasings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('moto_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vente_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date_debut');
            $table->unsignedInteger('duree_jours');           // ex : 180
            $table->unsignedBigInteger('montant_journalier');  // ex : 2000 FCFA/jour
            $table->unsignedBigInteger('montant_total');       // ex : 360000 FCFA (= duree * journalier)
            $table->string('frequence')->default('journalier'); // journalier | hebdomadaire | mensuel
            $table->string('statut')->default('actif');         // actif | termine | resilie
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contrat_leasings');
    }
};
