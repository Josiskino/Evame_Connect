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
        Schema::table('motos', function (Blueprint $table) {
            $table->string('marque')->default('Haojue')->index()->after('modele'); // filtre par marque
            $table->string('reference')->nullable()->unique()->after('marque');     // ex : EV001
            $table->boolean('leasing_eligible')->default(true)->after('prix');       // Leasing Oui/Non
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('motos', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropColumn(['marque', 'reference', 'leasing_eligible']);
        });
    }
};
