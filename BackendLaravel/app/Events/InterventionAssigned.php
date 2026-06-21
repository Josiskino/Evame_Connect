<?php

namespace App\Events;

use App\Models\Intervention;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Émis quand une intervention est assignée à un technicien SAV.
 * Diffusé sur le canal privé du technicien (`user.{id}`) -> popup + notification
 * temps réel sur son application mobile.
 */
class InterventionAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Intervention $intervention,
        public int $technicienId,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.'.$this->technicienId)];
    }

    public function broadcastAs(): string
    {
        return 'intervention.assigned';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $i = $this->intervention->loadMissing(['client', 'moto']);

        return [
            'id' => $i->id,
            'probleme' => $i->probleme,
            'statut' => $i->statut,
            'client' => $i->client?->nom,
            'moto' => $i->moto?->modele,
            'message' => 'Nouvelle mission : '.($i->client?->nom ?? 'client').' — '.($i->moto?->modele ?? 'moto'),
        ];
    }
}
