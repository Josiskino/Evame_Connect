<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Émis quand les accès d'un utilisateur changent (permission/rôle accordé ou révoqué).
 * Diffusé sur le canal privé de l'utilisateur pour mettre à jour ses vues EN TEMPS RÉEL.
 */
class UserAccessUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->user->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'access.updated';
    }

    /**
     * Charge utile : la liste à jour des vues et permissions de l'utilisateur.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'views' => $this->user->accessibleViews(),
            'permissions' => $this->user->getAllPermissions()->pluck('name')->values()->all(),
            'roles' => $this->user->getRoleNames()->values()->all(),
        ];
    }
}
