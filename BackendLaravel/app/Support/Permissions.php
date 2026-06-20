<?php

namespace App\Support;

/**
 * Catalogue centralisé des permissions de l'application.
 *
 * Deux familles :
 *  - les VUES/écrans (`view.*`) : pilotent l'affichage côté front et le retrait
 *    de vue en temps réel ;
 *  - les ACTIONS métier (`*.create`, `*.update`...).
 */
final class Permissions
{
    // --- Vues / écrans ---------------------------------------------------
    public const VIEW_DASHBOARD = 'view.dashboard';
    public const VIEW_CATALOGUE = 'view.catalogue';
    public const VIEW_VENTES = 'view.ventes';
    public const VIEW_CLIENTS = 'view.clients';
    public const VIEW_LEASING = 'view.leasing';
    public const VIEW_INTERVENTIONS = 'view.interventions';
    public const VIEW_ADMIN = 'view.admin';

    // --- Actions métier --------------------------------------------------
    public const CLIENT_CREATE = 'client.create';
    public const VENTE_CREATE = 'vente.create';
    public const LEASING_CREATE = 'leasing.create';
    public const PAIEMENT_CREATE = 'paiement.create';
    public const INTERVENTION_CREATE = 'intervention.create';
    public const INTERVENTION_UPDATE = 'intervention.update';
    public const RBAC_MANAGE = 'rbac.manage';

    /** @return array<int, string> Toutes les permissions de type « vue ». */
    public const VIEWS = [
        self::VIEW_DASHBOARD,
        self::VIEW_CATALOGUE,
        self::VIEW_VENTES,
        self::VIEW_CLIENTS,
        self::VIEW_LEASING,
        self::VIEW_INTERVENTIONS,
        self::VIEW_ADMIN,
    ];

    /** @return array<int, string> Toutes les permissions d'action. */
    public const ACTIONS = [
        self::CLIENT_CREATE,
        self::VENTE_CREATE,
        self::LEASING_CREATE,
        self::PAIEMENT_CREATE,
        self::INTERVENTION_CREATE,
        self::INTERVENTION_UPDATE,
        self::RBAC_MANAGE,
    ];

    /** @return array<int, string> L'ensemble des permissions. */
    public static function all(): array
    {
        return [...self::VIEWS, ...self::ACTIONS];
    }
}
