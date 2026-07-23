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
        Schema::create('demande_leasings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('moto_id')->constrained()->cascadeOnDelete();
            $table->string('numero')->nullable()->unique();     // ex : DEM-2026-0001
            $table->unsignedBigInteger('prix_comptant');
            $table->unsignedBigInteger('apport');               // 10 % du prix
            $table->unsignedBigInteger('montant_finance');
            $table->unsignedInteger('duree_jours');             // 180
            $table->unsignedBigInteger('cout_journalier');
            $table->unsignedBigInteger('cout_hebdomadaire');
            $table->unsignedBigInteger('cout_mensuel');
            $table->unsignedBigInteger('cout_total');
            $table->string('frequence')->default('journalier'); // journalier | hebdomadaire | mensuel
            $table->string('statut')->default('en_attente');    // en_attente | approuvee | refusee
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demande_leasings');
    }
};
