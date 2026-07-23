<?php

namespace App\Actions\Client\Commande;

use App\Exceptions\BusinessException;
use App\Models\Commande;
use App\Repositories\Contracts\CommandeRepositoryInterface;
use App\Repositories\Contracts\PanierRepositoryInterface;
use App\Repositories\Contracts\PieceRepositoryInterface;
use App\Support\ReferenceGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Cas d'usage : transformer le panier en commande (statut soumise, sans paiement).
 * Fige les prix, décrémente le stock (transaction + verrou), puis vide le panier.
 */
final class CreateCommandeAction
{
    public function __construct(
        private readonly PanierRepositoryInterface $paniers,
        private readonly PieceRepositoryInterface $pieces,
        private readonly CommandeRepositoryInterface $commandes,
    ) {}

    public function execute(int $clientId): Commande
    {
        $panier = $this->paniers->loadContents($this->paniers->firstOrCreateForClient($clientId));

        if ($panier->lignes->isEmpty()) {
            throw new BusinessException('Votre panier est vide.', 422);
        }

        return DB::transaction(function () use ($clientId, $panier) {
            $commande = $this->commandes->create([
                'client_id' => $clientId,
                'statut' => Commande::STATUT_SOUMISE,
                'total' => 0,
            ]);

            $total = 0;

            foreach ($panier->lignes as $ligne) {
                $piece = $this->pieces->lockAndFind($ligne->piece_id)
                    ?? throw new BusinessException('Pièce introuvable.', 404);

                if ($piece->stock < $ligne->quantite) {
                    throw new BusinessException("Stock insuffisant pour « {$piece->designation} ».", 422);
                }

                $commande->lignes()->create([
                    'piece_id' => $piece->id,
                    'quantite' => $ligne->quantite,
                    'prix_unitaire' => $piece->prix,
                ]);

                $this->pieces->decrementStock($piece, $ligne->quantite);
                $total += $ligne->quantite * $piece->prix;
            }

            $commande->numero = ReferenceGenerator::make('CMD', $commande->id);
            $commande->total = $total;
            $commande->save();

            $this->paniers->clearLines($panier);

            return $commande->load('lignes.piece');
        });
    }
}
