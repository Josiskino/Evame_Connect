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
            // Pièce d'identité (CNI) collectée à l'enregistrement du client.
            $table->string('cni_recto')->nullable()->after('adresse');
            $table->string('cni_verso')->nullable()->after('cni_recto');
            $table->date('cni_date_emission')->nullable()->after('cni_verso');
            $table->date('cni_date_expiration')->nullable()->after('cni_date_emission');
            $table->string('cni_lieu_emission')->nullable()->after('cni_date_expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'cni_recto', 'cni_verso', 'cni_date_emission',
                'cni_date_expiration', 'cni_lieu_emission',
            ]);
        });
    }
};
