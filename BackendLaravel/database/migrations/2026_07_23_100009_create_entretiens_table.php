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
        Schema::create('entretiens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('moto_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contrat_leasing_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');                 // vidange | plaquettes | revision
            $table->date('date_echeance');
            $table->date('effectue_le')->nullable();
            $table->timestamps();
            $table->unique(['contrat_leasing_id', 'type']); // un rappel par type et par contrat
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entretiens');
    }
};
