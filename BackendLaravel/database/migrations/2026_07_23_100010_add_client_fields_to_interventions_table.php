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
        Schema::table('interventions', function (Blueprint $table) {
            $table->string('numero_dossier')->nullable()->unique()->after('id'); // ex : DOS-2026-0001
            $table->string('categorie')->nullable()->after('probleme');          // moteur | freinage | ...
            $table->string('urgence')->default('moyenne')->after('categorie');    // faible | moyenne | elevee
            $table->string('photo_url')->nullable()->after('urgence');
            $table->string('source')->default('agence')->after('photo_url');      // agence | client
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interventions', function (Blueprint $table) {
            $table->dropUnique(['numero_dossier']);
            $table->dropColumn(['numero_dossier', 'categorie', 'urgence', 'photo_url', 'source']);
        });
    }
};
