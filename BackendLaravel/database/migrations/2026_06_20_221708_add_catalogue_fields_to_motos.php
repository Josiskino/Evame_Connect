<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motos', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('id');
            $table->string('famille')->nullable()->index()->after('modele'); // colonne_vertebrale | scooter | sous_los
            $table->string('classe_cc')->nullable()->index()->after('famille'); // 110CC, 125CC, 150CC...
            $table->string('puissance')->nullable()->after('cylindree');
            $table->string('couple')->nullable()->after('puissance');
            $table->json('images')->nullable()->after('image_url');
            $table->json('couleurs')->nullable()->after('images');
            $table->json('specifications')->nullable()->after('couleurs');
            $table->string('source_url')->nullable()->after('specifications');
        });
    }

    public function down(): void
    {
        Schema::table('motos', function (Blueprint $table) {
            $table->dropColumn([
                'slug', 'famille', 'classe_cc', 'puissance', 'couple',
                'images', 'couleurs', 'specifications', 'source_url',
            ]);
        });
    }
};
