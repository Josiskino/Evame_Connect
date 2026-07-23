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
        Schema::table('clients', function (Blueprint $table) {
            // Espace client B2C (application mobile).
            $table->string('ville')->nullable()->after('adresse');
            $table->string('quartier')->nullable()->after('ville');
            $table->string('photo_url')->nullable()->after('quartier');       // photo de profil
            $table->unsignedBigInteger('points_fidelite')->default(0)->after('photo_url');
            $table->string('source')->default('agence')->after('points_fidelite'); // agence | mobile
            $table->json('fcm_tokens')->nullable()->after('source');          // jetons push (un par appareil)

            // Le téléphone devient l'identifiant de connexion (OTP) -> unique.
            $table->unique('telephone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['telephone']);
            $table->dropColumn(['ville', 'quartier', 'photo_url', 'points_fidelite', 'source', 'fcm_tokens']);
        });
    }
};
