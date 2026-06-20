<?php

namespace App\Actions\Vente;

use App\Actions\Stock\DecrementStockAction;
use App\DTOs\Vente\CreateVenteData;
use App\Exceptions\BusinessException;
use App\Models\Vente;
use App\Repositories\Contracts\MotoRepositoryInterface;
use App\Repositories\Contracts\VenteRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cas d'usage : lancer une vente (parcours commercial).
 * Vérifie le stock, crée la vente, décrémente le stock et journalise le mouvement,
 * le tout dans une transaction.
 */
final class CreateVenteAction
{
    public function __construct(
        private readonly VenteRepositoryInterface $ventes,
        private readonly MotoRepositoryInterface $motos,
        private readonly DecrementStockAction $decrementStock,
    ) {}

    public function execute(CreateVenteData $data): Vente
    {
        return DB::transaction(function () use ($data) {
            $moto = $this->motos->lockAndFind($data->motoId)
                ?? throw new BusinessException('Moto introuvable.', 404);

            if ($moto->stock < 1) {
                throw new BusinessException("Cette moto n'est plus en stock.", 422, [
                    'moto_id' => ["Cette moto n'est plus en stock."],
                ]);
            }

            $vente = $this->ventes->create([
                'client_id' => $data->clientId,
                'moto_id' => $moto->id,
                'user_id' => $data->userId,
                'mode' => $data->mode,
                'montant' => $data->montant ?? $moto->prix,
                'date_vente' => $data->dateVente ?? Carbon::today()->format('Y-m-d'),
                'statut' => 'validee',
            ]);

            $this->decrementStock->execute(
                moto: $moto,
                quantite: 1,
                motif: 'Vente #'.$vente->id,
                reference: 'vente:'.$vente->id,
                userId: $data->userId,
            );

            return $this->ventes->find($vente->id);
        });
    }
}
