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
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('telephone')->index();               // numéro normalisé (228XXXXXXXX)
            $table->string('code_hash');                        // code OTP haché (jamais en clair)
            $table->timestamp('expires_at');                    // expiration du code (5 min)
            $table->unsignedTinyInteger('attempts')->default(0); // tentatives de vérification (anti-bruteforce)
            $table->timestamp('verified_at')->nullable();       // horodatage de validation du code
            $table->string('registration_token_hash')->nullable(); // ticket d'inscription (nouveau client), hash sha256
            $table->timestamp('registration_expires_at')->nullable();
            $table->timestamp('consumed_at')->nullable();       // ticket consommé (compte créé)
            $table->string('locale', 5)->default('fr');         // fr | en
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
