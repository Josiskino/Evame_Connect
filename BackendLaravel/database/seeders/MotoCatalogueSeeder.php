<?php

namespace Database\Seeders;

use App\Models\Moto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Peuple le catalogue motos à partir des données scrapées du site Haojue
 * (database/data/haojue_catalogue.json). Télécharge l'image principale de
 * chaque modèle dans storage/app/public/motos et applique des prix/stock de démo.
 */
class MotoCatalogueSeeder extends Seeder
{
    /** Fourchettes de prix de démo (FCFA) par classe de cylindrée. */
    private const PRIX = [
        '110CC' => [450_000, 600_000],
        '115CC' => [500_000, 650_000],
        '125CC' => [600_000, 850_000],
        '150CC' => [850_000, 1_150_000],
        '160CC' => [1_150_000, 1_350_000],
        '300CC' => [2_500_000, 3_500_000],
    ];

    /** Palette de couleurs (nom => hex) pour générer les coloris de démo. */
    private const PALETTE = [
        'Noir' => '#1C1C1C',
        'Rouge' => '#C62828',
        'Bleu' => '#1565C0',
        'Blanc' => '#ECEFF1',
        'Gris' => '#546E7A',
        'Vert' => '#2E7D32',
        'Orange' => '#EF6C00',
    ];

    /** Couple de référence (démo) quand absent de la fiche, par classe. */
    private const COUPLE = [
        '110CC' => '8.0 N•m (4500 r/min)',
        '115CC' => '8.4 N•m (5500 r/min)',
        '125CC' => '9.6 N•m (6500 r/min)',
        '150CC' => '11.4 N•m (6000 r/min)',
        '160CC' => '14 N•m (6500 r/min)',
        '300CC' => '27.8 N•m (6500 r/min)',
    ];

    public function run(): void
    {
        $path = database_path('data/haojue_catalogue.json');
        if (! is_file($path)) {
            $this->command?->warn("Catalogue introuvable : {$path}");

            return;
        }

        $motos = json_decode(file_get_contents($path), true) ?? [];
        Storage::disk('public')->makeDirectory('motos');

        foreach ($motos as $data) {
            $imagePath = $this->downloadImage($data);

            [$min, $max] = self::PRIX[$data['classe_cc']] ?? [500_000, 900_000];
            $prix = (int) (round(random_int($min, $max) / 5_000) * 5_000);
            $stock = $this->demoStock();

            Moto::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'modele' => $data['modele'],
                    'famille' => $data['famille'],
                    'classe_cc' => $data['classe_cc'],
                    'couleur' => null,
                    'couleurs' => $this->demoCouleurs($data['slug']),
                    'cylindree' => $data['specifications']['moteur']['cylindree'] ?? $data['classe_cc'],
                    'puissance' => $data['puissance'] ?? null,
                    'couple' => $data['couple'] ?? (self::COUPLE[$data['classe_cc']] ?? null),
                    'prix' => $prix,
                    'image_url' => $imagePath,
                    'images' => $imagePath ? [$imagePath] : [],
                    'specifications' => $data['specifications'] ?? null,
                    'source_url' => $data['source_url'] ?? null,
                    'stock' => $stock,
                    'seuil_alerte' => 3,
                ],
            );
        }

        $this->command?->info(count($motos).' motos du catalogue Haojue importées.');
    }

    /**
     * Coloris de démo déterministes (2 à 3 couleurs) basés sur le slug.
     *
     * @return array<int, array{nom:string, hex:string}>
     */
    private function demoCouleurs(string $slug): array
    {
        $keys = array_keys(self::PALETTE);
        $seed = crc32($slug);
        $count = 2 + ($seed % 2);

        $chosen = [];
        for ($i = 0; $i < $count; $i++) {
            $key = $keys[($seed + $i * 3) % count($keys)];
            $chosen[$key] = ['nom' => $key, 'hex' => self::PALETTE[$key]];
        }

        return array_values($chosen);
    }

    /**
     * Télécharge l'image principale dans storage/app/public/motos/{slug}.{ext}.
     * Retourne le chemin relatif (pour image_url) ou null en cas d'échec.
     *
     * @param  array<string, mixed>  $data
     */
    private function downloadImage(array $data): ?string
    {
        $url = $data['image_source'] ?? ($data['images'][0] ?? null);
        if (! $url) {
            return null;
        }

        try {
            $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'png';
            $relative = 'motos/'.$data['slug'].'.'.$ext;

            $response = Http::timeout(20)->retry(2, 500)->get($url);
            if (! $response->successful()) {
                return null;
            }

            Storage::disk('public')->put($relative, $response->body());

            return $relative;
        } catch (\Throwable $e) {
            $this->command?->warn("Image non téléchargée pour {$data['slug']} : ".$e->getMessage());

            return null;
        }
    }

    /** Stock de démo : majorité en stock, quelques alertes/ruptures. */
    private function demoStock(): int
    {
        return match (random_int(1, 10)) {
            1 => 0,                    // rupture
            2, 3 => random_int(1, 3),  // alerte stock faible
            default => random_int(5, 25),
        };
    }
}
