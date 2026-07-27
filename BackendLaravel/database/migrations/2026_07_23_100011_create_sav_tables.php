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
        Schema::create('centres_sav', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('adresse')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('telephone')->nullable();
            $table->json('horaires')->nullable();               // { "lundi": ["08:00","17:00"], ... }
            $table->unsignedInteger('capacite_creneau')->default(30); // RDV simultanés par créneau
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('rendez_vous', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('centre_sav_id')->constrained('centres_sav')->cascadeOnDelete();
            $table->foreignId('intervention_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('creneau');
            $table->string('statut')->default('confirme'); // confirme | annule | honore
            $table->timestamps();
        });

        Schema::table('interventions', function (Blueprint $table) {
            $table->foreignId('centre_sav_id')->nullable()->after('source')->constrained('centres_sav')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interventions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('centre_sav_id');
        });
        Schema::dropIfExists('rendez_vous');
        Schema::dropIfExists('centres_sav');
    }
};
