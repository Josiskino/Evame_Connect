<?php

use App\Support\ReferenceGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contrat_leasings', function (Blueprint $table) {
            $table->string('numero')->nullable()->unique()->after('id'); // ex : CT-2026-0001
        });

        // Backfill des contrats existants.
        foreach (DB::table('contrat_leasings')->whereNull('numero')->pluck('id') as $id) {
            DB::table('contrat_leasings')->where('id', $id)->update([
                'numero' => ReferenceGenerator::make('CT', $id),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contrat_leasings', function (Blueprint $table) {
            $table->dropUnique(['numero']);
            $table->dropColumn('numero');
        });
    }
};
