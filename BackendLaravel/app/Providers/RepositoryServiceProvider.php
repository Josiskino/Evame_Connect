<?php

namespace App\Providers;

use App\Repositories\Contracts\ClientAuthRepositoryInterface;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\CommandeRepositoryInterface;
use App\Repositories\Contracts\ContratLeasingRepositoryInterface;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Contracts\DemandeLeasingRepositoryInterface;
use App\Repositories\Contracts\InterventionRepositoryInterface;
use App\Repositories\Contracts\MotoRepositoryInterface;
use App\Repositories\Contracts\OtpRepositoryInterface;
use App\Repositories\Contracts\PanierRepositoryInterface;
use App\Repositories\Contracts\PieceRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Repositories\Contracts\VenteRepositoryInterface;
use App\Repositories\Eloquent\EloquentClientAuthRepository;
use App\Repositories\Eloquent\EloquentClientRepository;
use App\Repositories\Eloquent\EloquentCommandeRepository;
use App\Repositories\Eloquent\EloquentContratLeasingRepository;
use App\Repositories\Eloquent\EloquentDashboardRepository;
use App\Repositories\Eloquent\EloquentDemandeLeasingRepository;
use App\Repositories\Eloquent\EloquentInterventionRepository;
use App\Repositories\Eloquent\EloquentMotoRepository;
use App\Repositories\Eloquent\EloquentOtpRepository;
use App\Repositories\Eloquent\EloquentPanierRepository;
use App\Repositories\Eloquent\EloquentPieceRepository;
use App\Repositories\Eloquent\EloquentStockMovementRepository;
use App\Repositories\Eloquent\EloquentVenteRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Inversion de dépendance : on lie chaque interface (port) à son
 * implémentation Eloquent. Les Actions ne dépendent que des interfaces.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    private array $repositories = [
        MotoRepositoryInterface::class => EloquentMotoRepository::class,
        ClientRepositoryInterface::class => EloquentClientRepository::class,
        VenteRepositoryInterface::class => EloquentVenteRepository::class,
        ContratLeasingRepositoryInterface::class => EloquentContratLeasingRepository::class,
        InterventionRepositoryInterface::class => EloquentInterventionRepository::class,
        DashboardRepositoryInterface::class => EloquentDashboardRepository::class,
        StockMovementRepositoryInterface::class => EloquentStockMovementRepository::class,

        // Espace client B2C
        OtpRepositoryInterface::class => EloquentOtpRepository::class,
        ClientAuthRepositoryInterface::class => EloquentClientAuthRepository::class,
        DemandeLeasingRepositoryInterface::class => EloquentDemandeLeasingRepository::class,
        PieceRepositoryInterface::class => EloquentPieceRepository::class,
        PanierRepositoryInterface::class => EloquentPanierRepository::class,
        CommandeRepositoryInterface::class => EloquentCommandeRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->repositories as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        //
    }
}
