<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Émis à chaque création / modification / suppression d'une ressource métier
 * (client, vente, leasing, paiement, intervention…). Diffusé sur le canal
 * d'activité partagé pour que TOUS les utilisateurs connectés voient l'action
 * EN TEMPS RÉEL (notification + rafraîchissement des listes sans recharger).
 */
class ResourceChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $resource,
        public string $action,
        public int $id,
        public string $label,
        public ?User $actor = null,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('evame.activity')];
    }

    public function broadcastAs(): string
    {
        return 'resource.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'resource' => $this->resource,
            'action' => $this->action,
            'id' => $this->id,
            'label' => $this->label,
            'by' => $this->actor ? ['id' => $this->actor->id, 'name' => $this->actor->name] : null,
        ];
    }
}
