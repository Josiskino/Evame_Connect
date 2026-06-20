<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'telephone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles {
        hasPermissionTo as protected spatieHasPermissionTo;
    }

    /** Noms des rôles métier EVAME (gérés par Spatie). */
    public const ROLE_SUPER_ADMIN = 'super-admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_COMMERCIAL = 'commercial';
    public const ROLE_SAV = 'sav';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'denied_permissions' => 'array',
        ];
    }

    /**
     * Permissions explicitement refusées à cet utilisateur (override du rôle).
     *
     * @return array<int, string>
     */
    public function deniedPermissions(): array
    {
        return $this->denied_permissions ?? [];
    }

    public function isPermissionDenied(string $permission): bool
    {
        return in_array($permission, $this->deniedPermissions(), true);
    }

    /** Refuse une permission à cet utilisateur (retrait ciblé). */
    public function denyPermission(string $permission): void
    {
        $denied = $this->deniedPermissions();
        if (! in_array($permission, $denied, true)) {
            $denied[] = $permission;
            $this->denied_permissions = array_values($denied);
            $this->save();
        }
    }

    /** Lève le refus d'une permission (ré-autorisation). */
    public function allowPermission(string $permission): void
    {
        $this->denied_permissions = array_values(
            array_filter($this->deniedPermissions(), fn (string $p) => $p !== $permission)
        );
        $this->save();
    }

    /**
     * Surcharge Spatie : une permission explicitement refusée (override du rôle)
     * est niée, même si elle est héritée d'un rôle. C'est le point consulté par
     * Spatie (checkPermissionTo) -> garantit le retrait ciblé côté autorisation.
     */
    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        $name = is_string($permission) ? $permission : ($permission->name ?? null);

        if ($name !== null && ! $this->isSuperAdmin() && $this->isPermissionDenied($name)) {
            return false;
        }

        return $this->spatieHasPermissionTo($permission, $guardName);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }

    public function isCommercial(): bool
    {
        return $this->hasRole(self::ROLE_COMMERCIAL);
    }

    public function isSav(): bool
    {
        return $this->hasRole(self::ROLE_SAV);
    }

    public function isManager(): bool
    {
        return $this->hasRole(self::ROLE_MANAGER);
    }

    /**
     * Liste des vues/écrans accessibles à l'utilisateur (permissions `view.*`).
     * Le super admin a accès à toutes les vues (Gate::before).
     *
     * @return array<int, string>
     */
    public function accessibleViews(): array
    {
        if ($this->isSuperAdmin()) {
            return \App\Support\Permissions::VIEWS;
        }

        return $this->getAllPermissions()
            ->pluck('name')
            ->filter(fn (string $name) => in_array($name, \App\Support\Permissions::VIEWS, true))
            ->reject(fn (string $name) => $this->isPermissionDenied($name)) // retrait ciblé
            ->values()
            ->all();
    }

    /** Ventes réalisées par ce commercial. */
    public function ventes(): HasMany
    {
        return $this->hasMany(Vente::class);
    }

    /** Interventions assignées à ce technicien. */
    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class, 'technicien_id');
    }

    public function commentaires(): HasMany
    {
        return $this->hasMany(Commentaire::class);
    }
}
