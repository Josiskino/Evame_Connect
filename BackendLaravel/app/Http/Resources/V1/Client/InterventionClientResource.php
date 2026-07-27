<?php

namespace App\Http\Resources\V1\Client;

use App\Models\Intervention;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Dossier SAV exposé au client (déclaration + suivi).
 *
 * @mixin Intervention
 */
class InterventionClientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_dossier' => $this->numero_dossier,
            'categorie' => $this->categorie,
            'urgence' => $this->urgence,
            'description' => $this->probleme,
            'photo_url' => $this->resolveImageUrl($this->photo_url),
            'statut' => $this->statut,
            'statut_label' => $this->statutLabel(),
            'date_intervention' => $this->date_intervention?->format('Y-m-d'),
            'timeline' => $this->timeline(),
            'moto' => new MotoCatalogueResource($this->whenLoaded('moto')),
        ];
    }

    private function statutLabel(): string
    {
        return match ($this->statut) {
            Intervention::STATUT_NOUVELLE => 'Déclarée',
            Intervention::STATUT_EN_TRAITEMENT => 'En cours',
            Intervention::STATUT_TERMINEE => 'Terminée',
            default => $this->statut,
        };
    }

    /**
     * Étapes d'avancement du dossier (stepper côté mobile).
     *
     * @return array<int, array{statut:string, label:string, atteint:bool}>
     */
    private function timeline(): array
    {
        $labels = [
            Intervention::STATUT_NOUVELLE => 'Déclarée',
            Intervention::STATUT_EN_TRAITEMENT => 'En cours',
            Intervention::STATUT_TERMINEE => 'Terminée',
        ];

        $courant = array_search($this->statut, Intervention::STATUTS, true);
        $courant = $courant === false ? 0 : $courant;

        $timeline = [];
        foreach (Intervention::STATUTS as $index => $statut) {
            $timeline[] = [
                'statut' => $statut,
                'label' => $labels[$statut],
                'atteint' => $index <= $courant,
            ];
        }

        return $timeline;
    }

    private function resolveImageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}
