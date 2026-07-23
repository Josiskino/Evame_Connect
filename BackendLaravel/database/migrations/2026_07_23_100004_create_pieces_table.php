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
        Schema::create('pieces', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();          // ex : P001
            $table->string('designation');
            $table->unsignedBigInteger('prix');             // FCFA
            $table->string('image_url')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('compatibilite')->nullable();    // ex : « TVS HLX125, HLX150 »
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pieces');
    }
};
